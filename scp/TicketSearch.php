<?php

/*************************************************************************
    Ticket Search now lives in the Tbyte Portal:
        https://portal.remoteit.co.uk/ticket-search/

    The portal asks this ticket system's read-only search endpoint
    (include/api.ticketlist.php, GET /api/http.php/tickets/search.json) for the
    results, so the search runs here without the page doing any SQL of its own.
    This file only forwards, so the "Ticket Search" tab and old links and
    bookmarks (?keyword=...&andor=and|or|phrase) keep working. The page that
    used to be here pasted the keyword straight into its SQL.

    It reads nothing and touches no database, so it needs no login here - the
    portal asks for one.
**********************************************************************/

$q = trim((string) ($_GET['keyword'] ?? ''));
$andor = strtolower((string) ($_GET['andor'] ?? 'and'));

$params = array();
if ($q !== '')
    $params['q'] = $q;
if (in_array($andor, array('or', 'phrase'), true))
    $params['mode'] = $andor;

header('Location: https://portal.remoteit.co.uk/ticket-search/'
    . ($params ? '?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986) : ''), true, 302);
exit;
