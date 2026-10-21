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
     * GET /api/http.php/tickets/search.json?q=vpn+password[&limit=5]
     * Header: X-API-Key: <key>      (same read-only, IP-bound key as open.json)
     *
     * The tickets - any status except deleted - whose subject, number or
     * conversation (every message, reply and internal note, as osTicket's own
     * search index holds it) matches EVERY word in q, each word anywhere in
     * that ticket: "vpn password" finds a ticket with vpn in its subject and
     * password in a later reply. Words match as substrings, case-insensitively
     * (up to 6 words, 3 characters at least altogether). Each ticket comes back
     * once however many of its messages matched, last updated first.
     *
     *   200 {"total": 37, "tickets": [{"id", "number", "subject", "status",
     *        "created", "updated", "user", "org"}, ...]}
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
        $words = array_slice(array_values(array_unique(explode(' ', mb_substr($q, 0, 200)))), 0, self::MAX_WORDS);
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : self::DEFAULT_LIMIT;
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        // Where the words can be found: the subject, the ticket number, or any
        // message in the ticket's thread (osTicket's search table, object type H =
        // thread entry). Each place is read ONCE however many words there are:
        // a row's "mask" has bit i set when word i is in it, the masks of a
        // ticket's rows are OR-ed together, and the ticket matches when every
        // word's bit is set (the HAVING below) - each word may be in a different place.
        $likes = array();
        foreach ($words as $i => $word)
            $likes[$i] = db_input('%'.addcslashes($word, '\\%_').'%');
        $mask = function ($columns) use ($likes) {
            $bits = array();
            foreach ($likes as $i => $like) {
                $found = array();
                foreach ($columns as $column)
                    $found[] = $column.' LIKE '.$like;
                $bits[] = 'COALESCE('.implode(' OR ', $found).', 0) * '.(1 << $i);
            }
            return implode(' + ', $bits);
        };
        $any = function ($columns) use ($likes) {
            $found = array();
            foreach ($likes as $like)
                foreach ($columns as $column)
                    $found[] = $column.' LIKE '.$like;
            return '('.implode(' OR ', $found).')';
        };
        $entry = array('se.content', 'se.title');
        $branches = array(
            'SELECT ticket_id, '.$mask(array('subject')).' AS mask FROM '.TICKET_CDATA_TABLE.' WHERE '.$any(array('subject')),
            'SELECT ticket_id, '.$mask(array('number')).' AS mask FROM '.TICKET_TABLE.' WHERE '.$any(array('number')),
            'SELECT th.object_id AS ticket_id, '.$mask($entry).' AS mask FROM '.TABLE_PREFIX.'_search se'
                .' JOIN '.THREAD_ENTRY_TABLE.' te ON te.id = se.object_id'
                .' JOIN '.THREAD_TABLE.' th ON th.id = te.thread_id AND th.object_type = \'T\''
                .' WHERE se.object_type = \'H\' AND '.$any($entry),
        );

        // A runaway scan must not hold the ticket system up (MariaDB; harmless elsewhere).
        db_query('SET SESSION max_statement_time = 15', false);

        $res = db_query('SELECT t.ticket_id FROM (SELECT ticket_id FROM ('.implode(' UNION ALL ', $branches).') x'
            .' GROUP BY ticket_id HAVING BIT_OR(mask) = '.((1 << count($words)) - 1).') m'
            .' JOIN '.TICKET_TABLE.' t ON t.ticket_id = m.ticket_id'
            .' JOIN '.TICKET_STATUS_TABLE.' s ON s.id = t.status_id AND s.state <> \'deleted\''
            .' ORDER BY t.lastupdate DESC, t.ticket_id DESC LIMIT '.self::MAX_MATCHES);
        if (!$res)
            return $this->exerr(500, 'Ticket search failed');
        $ids = array();
        while ($row = db_fetch_row($res))
            $ids[] = (int) $row[0];
        $total = count($ids);

        // Details for the newest few only.
        $tickets = array();
        if ($ids) {
            $top = array_slice($ids, 0, $limit);
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

        Http::response(200, json_encode(array('total' => $total, 'tickets' => $tickets), JSON_INVALID_UTF8_SUBSTITUTE), 'application/json');
    }
}
