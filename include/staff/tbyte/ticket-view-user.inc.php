<?php
/**
 * Tetrabyte: the contact authorisation flags (authorised for decisions / spending,
 * important contact, user left) after the user's name on the ticket page - the same
 * flags User Search shows, from the same code (include/tbyte.contactauth.php).
 *
 * Included, with one line, from the User row of include/staff/ticket-view.inc.php, so
 * it runs in that template's scope ($ticket). Shows nothing when there is nothing to show.
 */
if (!defined('OSTSCPINC') || !isset($ticket))
    return;

require_once INCLUDE_DIR.'tbyte.contactauth.php';

$__tbyteFlags = TbyteContactAuth::ticketFlagsHtml($ticket->getOwnerId());
if ($__tbyteFlags !== '')
    echo '<span class="tbyte-auth-flags" style="margin-left:.5rem; white-space:nowrap;">'.$__tbyteFlags.'</span>';
