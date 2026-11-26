<?php
/*********************************************************************
    tbyte-ticket-unlock.php

    Tetrabyte: clears another agent's lock on a ticket. Called by the
    "Unlock Ticket" button on the lock notice
    (include/staff/tbyte/ticket-view-unlock.inc.php).

    POST id=<ticket id>. Any agent who can open the ticket may unlock it.
    staff.inc.php enforces the sign-in and the CSRF token.
**********************************************************************/
// As scp/ajax.php: answer 403, not the login page, when signed out.
function staffLoginPage($msg='Unauthorized') {
    Http::response(403, 'Must login: '.Format::htmlchars($msg));
    exit;
}

define('AJAX_REQUEST', 1);
require('staff.inc.php');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

require_once(INCLUDE_DIR.'class.ticket.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST')
    Http::response(405, 'POST required');

if (!($ticket = Ticket::lookup((int) $_POST['id']))
        || !$ticket->checkStaffPerm($thisstaff))
    Http::response(403, 'Access denied');

// No lock, an expired one or our own: nothing to clear.
if (($lock = $ticket->getLock()) && $lock->getStaffId() != $thisstaff->getId()) {
    $holder = (string) $lock->getStaffName();
    if (!$ticket->releaseLock())
        Http::response(500, 'Unable to unlock the ticket');

    // System Logs record of who took over from whom (no admin alert email).
    $ost->logWarning('Ticket unlocked', Format::htmlchars(sprintf(
            '%s unlocked ticket #%s, which was locked by %s',
            $thisstaff->getName(), $ticket->getNumber(), $holder)), false);
}

Http::response(200, '{"ok":true}', 'application/json');
