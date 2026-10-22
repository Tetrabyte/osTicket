<?php

include_once INCLUDE_DIR.'class.api.php';

/**
 * Tetrabyte addition (2026-10-20): GET /api/http.php/tickets/open.json
 *
 * Read-only lookup for the Tbyte Portal's Omnisearch (home page): the most
 * recent OPEN tickets of one ticket user, or of one organisation. It exists so
 * the portal never needs a database grant on the ticket tables (ticket subjects
 * are customer content) - this endpoint is the only door, it can only ask one
 * question, and it can only be asked by one machine.
 *
 *   GET /api/http.php/tickets/open.json?user_id=123           (or ?org_id=45)
 *       [&limit=5]                                             (1-10, default 5)
 *   Header: X-API-Key: <key>
 *
 *   200 {"total": 12, "tickets": [{"id", "number", "subject", "status",
 *        "created", "updated", "user"}, ...]}      newest created first
 *   "total" is how many open tickets there are altogether (the list is cut to limit).
 *   "open" = any status whose state is "open" (Open, Hold, Awaiting ...); closed
 *   and deleted tickets are never returned.
 *
 * Who may call it - all three must hold:
 *   - an active API key,
 *   - the key is READ-ONLY: neither "Can Create Tickets" nor "Can Execute Cron"
 *     is ticked, so a leaked ticket-creating key cannot read tickets and this
 *     key cannot create anything,
 *   - the request comes from the IP the key was issued for (this fork's
 *     ApiController::getKey() looks keys up without the IP, so it is checked here).
 * Ids are cast to integers; nothing from the request reaches the SQL as text.
 *
 * Tetrabyte addition (2026-10-21): GET /api/http.php/tickets/search.json - see search().
 */
class TicketListApiController extends ApiController {

    const DEFAULT_LIMIT = 5;
    const MAX_LIMIT = 10;
    // search.json only: the portal's Ticket Search page asks for every match in one go
    // (a search is the slow part, so it pages, sorts and filters the rows it has).
    const SEARCH_MAX_LIMIT = 5000;
    const MAX_WORDS = 6;
    const MAX_MATCHES = 5000;

    // The three conditions every endpoint here needs (see the class comment).
    // Answers the 401 itself and returns false when one fails.
    private function authorise() {
        if (!($key = $this->requireApiKey()))
            return false;
        if ($key->canCreateTickets() || $key->canExecuteCron()) {
            $this->exerr(401, 'This endpoint needs a read-only API key (no Can Create Tickets / Can Execute Cron)');
            return false;
        }

        $registered = trim((string) $key->getIPAddr());
        $remote = (string) $this->getRemoteAddr();
        if ($registered === '' || $registered !== $remote) {
            $this->exerr(401, sprintf('API key is not registered for this source address (%s)', $remote));
            return false;
        }

        return true;
    }

    function recent($format = 'json') {
        if (!$this->authorise())
            return;

        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $org_id = isset($_GET['org_id']) ? (int) $_GET['org_id'] : 0;
        if (($user_id > 0) === ($org_id > 0))
            return $this->exerr(400, 'Give exactly one of user_id or org_id');
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : self::DEFAULT_LIMIT;
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $from = ' FROM '.TICKET_TABLE.' t'
            .' JOIN '.TICKET_STATUS_TABLE.' s ON s.id = t.status_id AND s.state = \'open\''
            .' LEFT JOIN '.USER_TABLE.' u ON u.id = t.user_id'
            .' LEFT JOIN '.TICKET_CDATA_TABLE.' cd ON cd.ticket_id = t.ticket_id'
            .' WHERE '.($user_id > 0 ? 't.user_id = '.$user_id : 'u.org_id = '.$org_id);

        $total = 0;
        if (($res = db_query('SELECT COUNT(*)'.$from)) && ($row = db_fetch_row($res)))
            $total = (int) $row[0];

        $tickets = array();
        $res = db_query('SELECT t.ticket_id, t.number, t.created, t.lastupdate, s.name AS status, cd.subject, u.name AS user_name'
            .$from.' ORDER BY t.created DESC, t.ticket_id DESC LIMIT '.$limit);
        while ($res && ($row = db_fetch_array($res))) {
            $tickets[] = array(
                'id'      => (int) $row['ticket_id'],
                'number'  => $row['number'],
                'subject' => (string) $row['subject'],
                'status'  => $row['status'],
                'created' => $row['created'],
                'updated' => $row['lastupdate'],
                'user'    => (string) $row['user_name'],
            );
        }

        Http::response(200, json_encode(array('total' => $total, 'tickets' => $tickets), JSON_INVALID_UTF8_SUBSTITUTE), 'application/json');
    }

    /**
     * GET /api/http.php/tickets/search.json?q=vpn+password[&mode=and|or|phrase][&limit=5][&offset=0]
     * Header: X-API-Key: <key>      (same read-only, IP-bound key as open.json)
     *
     * The tickets - any status except deleted - whose subject, number or
     * conversation (every message, reply and internal note, as osTicket's own
     * search index holds it) matches the words in q, each word anywhere in
     * that ticket: "vpn password" finds a ticket with vpn in its subject and
     * password in a later reply. mode picks how the words combine - and (the
     * default: every word), or (any word) or phrase (q as ONE piece of text, the
     * words next to each other in that order). q is always plain text - no
     * wildcards or operators of any kind - and reaches the SQL only as an escaped
     * literal. limit is 1-100 (default 5), offset skips that many matches, for
     * paging. In the subject and number a word matches as a
     * substring, case-insensitively; in the conversation it matches a word that
     * starts with it (vpn finds vpns - osTicket's FULLTEXT index is used when it
     * can be, which is what makes this fast). Up to 6 words, 3 characters at
     * least altogether. Each ticket comes back
     * once however many of its messages matched, last updated first - except that
     * a ticket whose number is exactly one of the words (100123 or T100123, with
     * or without a leading #) comes first.
     *
     *   200 {"total": 37, "tickets": [{"id", "number", "subject", "status",
     *        "created", "updated", "user", "org"}, ...], "mode", "offset", "limit"}
     *   "total" is how many tickets matched altogether (counted up to 5000).
     *
     * Only ticket headers are returned, never the text that matched.
     */
    function search($format = 'json') {
        if (!$this->authorise())
            return;

        $q = trim(preg_replace('/\s+/u', ' ', (string) ($_GET['q'] ?? '')));
        if (mb_strlen($q) < 3)
            return $this->exerr(400, 'Give q: at least 3 characters');
        $mode = strtolower((string) ($_GET['mode'] ?? 'and'));
        if (!in_array($mode, array('and', 'or', 'phrase'), true))
            return $this->exerr(400, 'mode is and, or or phrase');
        // A leading # is dropped from each word, so a ticket number can be typed
        // as it is written ("#T123456") as well as bare. A phrase is one "word".
        $words = array();
        if ($mode === 'phrase') {
            $phrase = ltrim(mb_substr($q, 0, 200), '#');
            if ($phrase !== '')
                $words[] = $phrase;
        } else {
            foreach (explode(' ', mb_substr($q, 0, 200)) as $word) {
                $word = ltrim($word, '#');
                if ($word !== '')
                    $words[$word] = $word;
            }
            $words = array_slice(array_values($words), 0, self::MAX_WORDS);
        }
        if (!$words)
            return $this->exerr(400, 'Give q: at least 3 characters');
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : self::DEFAULT_LIMIT;
        $limit = max(1, min(self::SEARCH_MAX_LIMIT, $limit));
        $offset = max(0, min(self::MAX_MATCHES, (int) ($_GET['offset'] ?? 0)));

        // Words the full-text index can't hold (shorter than its minimum, or a
        // stopword like "to" or "the") are left out when other words remain: one
        // would force a read of every message's text (~14 s) and narrows nothing
        // useful. They come back as "ignored" so the page can say so.
        $fulltext = $this->fulltextInfo();
        $ignored = array();
        if ($fulltext && $mode !== 'phrase' && count($words) > 1) {
            $usable = array();
            foreach ($words as $word) {
                if ($this->fulltextTokens($word, $fulltext))
                    $usable[] = $word;
                else
                    $ignored[] = $word;
            }
            if ($usable)
                $words = $usable;
            else
                $ignored = array();
        }

        // Where the words can be found: the subject, the ticket number, or any
        // message in the ticket's thread (osTicket's search table, object type H =
        // thread entry). A row's "mask" has bit i set when word i is in it, the
        // masks of a ticket's rows are OR-ed together, and the ticket matches when
        // every word's bit is set (the HAVING below) - each word may be in a
        // different place.
        $likes = array();
        foreach ($words as $i => $word)
            $likes[$i] = db_input('%'.addcslashes($word, '\\%_').'%');
        $mask = function ($columns, $indexes) use ($likes) {
            $bits = array();
            foreach ($indexes as $i) {
                $found = array();
                foreach ($columns as $column)
                    $found[] = $column.' LIKE '.$likes[$i];
                $bits[] = 'COALESCE('.implode(' OR ', $found).', 0) * '.(1 << $i);
            }
            return implode(' + ', $bits);
        };
        $any = function ($columns, $indexes) use ($likes) {
            $found = array();
            foreach ($indexes as $i)
                foreach ($columns as $column)
                    $found[] = $column.' LIKE '.$likes[$i];
            return '('.implode(' OR ', $found).')';
        };
        $all = array_keys($words);
        $entry = array('se.content', 'se.title');
        $branches = array(
            'subject' => 'SELECT ticket_id, '.$mask(array('subject'), $all).' AS mask FROM '.TICKET_CDATA_TABLE.' WHERE '.$any(array('subject'), $all),
            'number' => 'SELECT ticket_id, '.$mask(array('number'), $all).' AS mask FROM '.TICKET_TABLE.' WHERE '.$any(array('number'), $all),
        );

        // The conversations are the big part: reading every message's text for a
        // substring took ~14 s here. So a word is first looked up in the search
        // table's FULLTEXT index (what osTicket's own search uses - milliseconds),
        // and, for a word that is more than one index word (john@acme.com), the
        // messages it finds are then checked for the whole substring. For a plain
        // word the index answer is the answer - reading the text of the thousands of
        // messages that mention a common word (vpn) took 4-15 s, and tells nothing
        // new. The index finds words that START with the typed one (vpn finds vpn and
        // vpns, not "anyvpn"). A word the index can't hold (shorter than its minimum,
        // or a stopword like "the") is still read from the text, in one pass for all
        // such words; if the table isn't InnoDB with that index, every word is.
        $from_entry = ' FROM '.TABLE_PREFIX.'_search se'
            .' JOIN '.THREAD_ENTRY_TABLE.' te ON te.id = se.object_id'
            .' JOIN '.THREAD_TABLE.' th ON th.id = te.thread_id AND th.object_type = \'T\'';
        $scan = array();
        foreach ($words as $i => $word) {
            $tokens = $fulltext ? $this->fulltextTokens($word, $fulltext) : array();
            if (!$tokens) {
                $scan[] = $i;
                continue;
            }
            $plain = count($tokens) === 1 && $tokens[0] === mb_strtolower($word);
            $branches['text:'.$word] = 'SELECT th.object_id AS ticket_id, '.(1 << $i).' AS mask'.$from_entry
                .' WHERE se.object_type = \'H\''
                .' AND MATCH(se.title, se.content) AGAINST('.db_input('+'.implode('* +', $tokens).'*').' IN BOOLEAN MODE)'
                .($plain ? '' : ' AND '.$any($entry, array($i)));
        }
        if ($scan)
            $branches['scan'] = 'SELECT th.object_id AS ticket_id, '.$mask($entry, $scan).' AS mask'.$from_entry
                .' WHERE se.object_type = \'H\' AND '.$any($entry, $scan);
        $method = !$scan ? 'fulltext' : (count($scan) < count($words) ? 'mixed' : 'scan');

        // A ticket whose number IS one of the words (as typed, or with the T that
        // 7-character numbers start with: 100123 = T100123) goes first - a number
        // also turns up in other tickets' conversations, and the ticket itself must
        // not be pushed out of the few returned by more recently updated ones.
        $exact = array();
        foreach ($words as $word) {
            $exact[] = db_input($word);
            if (ctype_digit($word))
                $exact[] = db_input('T'.$word);
        }

        // A broad search (a common word) takes 5-25 s of the ticket database. A page that
        // asks again, quick clicks and requests the portal has already given up on all
        // keep running here, and stacked up they push each other past the limit below.
        // So at most two run at once (named database locks, which go when the
        // connection does): a third waits a few seconds for one to finish, then is told
        // it is busy.
        $slot = 0;
        foreach (array(1, 2) as $n) {
            if ($this->takeSearchSlot($n, 0)) {
                $slot = $n;
                break;
            }
        }
        if (!$slot && $this->takeSearchSlot(1, 8))
            $slot = 1;
        if (!$slot)
            return $this->exerr(503, 'Ticket search is busy with other searches - try again in a moment');

        // A runaway scan must not hold the ticket system up (MariaDB; harmless elsewhere).
        db_query('SET SESSION max_statement_time = 30', false);

        $started = microtime(true);
        $res = db_query('SELECT t.ticket_id FROM (SELECT ticket_id FROM ('.implode(' UNION ALL ', $branches).') x'
            // and: every word's bit set; or: any bit set (every row already has one).
            .' GROUP BY ticket_id HAVING BIT_OR(mask) '.($mode === 'or' ? '> 0' : '= '.((1 << count($words)) - 1)).') m'
            .' JOIN '.TICKET_TABLE.' t ON t.ticket_id = m.ticket_id'
            .' JOIN '.TICKET_STATUS_TABLE.' s ON s.id = t.status_id AND s.state <> \'deleted\''
            .' ORDER BY (t.number IN ('.implode(',', $exact).')) DESC, t.lastupdate DESC, t.ticket_id DESC LIMIT '.self::MAX_MATCHES);
        if (!$res)
            return $this->exerr(500, 'Ticket search failed');
        $ids = array();
        while ($row = db_fetch_row($res))
            $ids[] = (int) $row[0];
        $total = count($ids);
        $ms = (int) round((microtime(true) - $started) * 1000);
        db_query('DO RELEASE_LOCK(\'tbyte_ticket_search_'.$slot.'\')', false);

        // ?debug=1: where the time went - each place the words are looked up, run on
        // its own, with the rows it finds (tuning only; costs about as much again).
        $timings = null;
        if (!empty($_GET['debug'])) {
            $timings = array();
            foreach ($branches as $label => $sql) {
                $t = microtime(true);
                $res = db_query('SELECT COUNT(*) FROM ('.$sql.') b', false);
                $row = $res ? db_fetch_row($res) : null;
                $timings[$label] = array('rows' => $row ? (int) $row[0] : null, 'ms' => (int) round((microtime(true) - $t) * 1000));
            }
        }

        // Details for the newest few only.
        $tickets = array();
        if ($ids) {
            $top = array_slice($ids, $offset, $limit);
            $res = db_query('SELECT t.ticket_id, t.number, t.created, t.lastupdate, s.name AS status, cd.subject, u.name AS user_name, o.name AS org_name'
                .' FROM '.TICKET_TABLE.' t'
                .' JOIN '.TICKET_STATUS_TABLE.' s ON s.id = t.status_id'
                .' LEFT JOIN '.USER_TABLE.' u ON u.id = t.user_id'
                .' LEFT JOIN '.ORGANIZATION_TABLE.' o ON o.id = u.org_id'
                .' LEFT JOIN '.TICKET_CDATA_TABLE.' cd ON cd.ticket_id = t.ticket_id'
                .' WHERE t.ticket_id IN ('.implode(',', $top).')');
            $by_id = array();
            while ($res && ($row = db_fetch_array($res))) {
                $by_id[(int) $row['ticket_id']] = array(
                    'id'      => (int) $row['ticket_id'],
                    'number'  => $row['number'],
                    'subject' => (string) $row['subject'],
                    'status'  => $row['status'],
                    'created' => $row['created'],
                    'updated' => $row['lastupdate'],
                    'user'    => (string) $row['user_name'],
                    'org'     => (string) $row['org_name'],
                );
            }
            foreach ($top as $id) {
                if (isset($by_id[$id]))
                    $tickets[] = $by_id[$id];
            }
        }

        // "method" and "ms" say how the conversations were searched and how long the
        // match took - for tuning, not for display.
        $answer = array('total' => $total, 'tickets' => $tickets, 'mode' => $mode, 'offset' => $offset, 'limit' => $limit, 'method' => $method, 'ms' => $ms);
        if ($ignored)
            $answer['ignored'] = $ignored;
        if ($timings !== null)
            $answer['branches'] = $timings;
        Http::response(200, json_encode($answer, JSON_INVALID_UTF8_SUBSTITUTE), 'application/json');
    }

    // Tries to take search slot $n (a named database lock), waiting up to $wait
    // seconds. A lock belongs to the database connection, so it is freed when the
    // request ends even if this code never gets to release it.
    private function takeSearchSlot($n, $wait) {
        $res = db_query('SELECT GET_LOCK(\'tbyte_ticket_search_'.(int) $n.'\', '.(int) $wait.')', false);
        $row = $res ? db_fetch_row($res) : null;
        return $row && (int) $row[0] === 1;
    }

    // Whether osTicket's search table can be searched through its FULLTEXT index,
    // and what that index holds: array('min' => the shortest word it stores, 'stop'
    // => the words it leaves out), or null when the plain read has to be used (not
    // an InnoDB table, no FULLTEXT index on title + content, or a custom stopword table).
    private function fulltextInfo() {
        $table = db_input(TABLE_PREFIX.'_search');
        $res = db_query('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.$table, false);
        if (!($res && ($row = db_fetch_row($res)) && !strcasecmp((string) $row[0], 'InnoDB')))
            return null;
        $res = db_query('SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.$table
            .' AND INDEX_TYPE = \'FULLTEXT\' GROUP BY INDEX_NAME HAVING COUNT(*) = 2 AND SUM(COLUMN_NAME IN (\'title\', \'content\')) = 2', false);
        if (!($res && db_fetch_row($res)))
            return null;
        $vars = array();
        $res = db_query('SHOW VARIABLES LIKE \'innodb_ft_%\'', false);
        while ($res && ($row = db_fetch_row($res)))
            $vars[$row[0]] = $row[1];
        if (trim((string) ($vars['innodb_ft_server_stopword_table'] ?? '')) !== '')
            return null;
        // InnoDB's built-in stopword list (MySQL and MariaDB).
        $stop = (($vars['innodb_ft_enable_stopword'] ?? 'ON') === 'OFF') ? array() : array(
            'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i', 'in', 'is', 'it', 'la',
            'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'www');
        return array('min' => max(1, (int) ($vars['innodb_ft_min_token_size'] ?? 3)), 'stop' => $stop);
    }

    // The index words a typed word is looked up by: its letter/digit runs that the
    // index can hold ("john@acme.com" -> john, acme; "com" is a stopword). Empty
    // when none can be - the word then has to be read from the text.
    private function fulltextTokens($word, $ft) {
        $tokens = array();
        foreach (preg_split('/[^\p{L}\p{N}_]+/u', mb_strtolower($word), -1, PREG_SPLIT_NO_EMPTY) as $token) {
            $length = mb_strlen($token);
            if ($length >= $ft['min'] && $length <= 80 && !in_array($token, $ft['stop'], true))
                $tokens[$token] = $token;
        }
        return array_values($tokens);
    }
}
