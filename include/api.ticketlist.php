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
 */
class TicketListApiController extends ApiController {

    const DEFAULT_LIMIT = 5;
    const MAX_LIMIT = 10;

    function recent($format = 'json') {
        if (!($key = $this->requireApiKey()))
            return;
        if ($key->canCreateTickets() || $key->canExecuteCron())
            return $this->exerr(401, 'This endpoint needs a read-only API key (no Can Create Tickets / Can Execute Cron)');

        $registered = trim((string) $key->getIPAddr());
        $remote = (string) $this->getRemoteAddr();
        if ($registered === '' || $registered !== $remote)
            return $this->exerr(401, sprintf('API key is not registered for this source address (%s)', $remote));

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
}
