<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.ticket.php';
include_once INCLUDE_DIR.'class.staff.php';

/**
 * Tetrabyte addition (2026-11-17): POST /api/http.php/tickets/note.json
 *
 * Adds ONE internal staff note to an existing ticket. Used by the Tbyte Portal's automatic user
 * disable (leaver requests raised through the portal's ticket forms): when it queues, runs, fails or
 * cancels a disable it records that on the ticket, as the poster "support-tbyte". It exists so the
 * portal never needs write access to the ticket database - this endpoint is the only door and it
 * can do exactly one thing: add a note. No email is sent (alert off), nothing is changed on the
 * ticket besides the note (no status change, no assignment).
 *
 *   POST /api/http.php/tickets/note.json
 *   Header: X-API-Key: <key>
 *   Body (JSON): {"number": "T721271", "note": "text", "title": "Automatic user disable"}
 *     number  the ticket NUMBER as shown to staff (not the internal id)
 *     note    1 - 8000 characters, plain text (shown as text, tags are not interpreted)
 *     title   optional, up to 100 characters (the note's heading)
 *     poster  ignored: the poster is always support-tbyte - the agent with that username if the
 *             ticket system has one, otherwise just that name
 *
 *   200 {"ok": true, "note_id": 123}     400 bad request     401 key not allowed     404 no such ticket
 *
 * Same key rules as /api/users.json: the key must be active, allowed to create tickets, and
 * registered to the IP the request comes from (this fork's ApiController::getKey() looks keys up
 * without the IP, so it is checked here).
 */
class TicketNoteApiController extends ApiController {

    const POSTER = 'support-tbyte';
    const MAX_NOTE = 8000;
    const MAX_TITLE = 100;

    function post($format = 'json') {
        if (!($key = $this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        $registered = trim((string) $key->getIPAddr());
        $remote = (string) $this->getRemoteAddr();
        if ($registered === '' || $registered !== $remote)
            return $this->exerr(401, sprintf('API key is not registered for this source address (%s)', $remote));

        $data = $this->getRequest('json', false);
        $number = is_array($data) && isset($data['number']) ? trim((string) $data['number']) : '';
        $note = is_array($data) && isset($data['note']) ? trim((string) $data['note']) : '';
        $title = is_array($data) && isset($data['title']) ? trim((string) $data['title']) : '';

        // Ticket numbers are letters/digits only (e.g. T721271) - nothing else reaches the lookup.
        if ($number === '' || !preg_match('/^[A-Za-z0-9]{1,20}$/', $number))
            return $this->exerr(400, 'A valid ticket number is required');
        if ($note === '' || strlen($note) > self::MAX_NOTE)
            return $this->exerr(400, sprintf('The note must be 1 to %d characters', self::MAX_NOTE));
        if (strlen($title) > self::MAX_TITLE)
            $title = substr($title, 0, self::MAX_TITLE);

        if (!($ticket = Ticket::lookupByNumber($number)))
            return $this->exerr(404, 'Ticket not found');

        // The note body is treated as plain text by osTicket's thread entry body when given as
        // text, so nothing the portal sends can inject markup into the thread.
        $vars = array(
            'note' => new TextThreadEntryBody($note),
            'title' => $title !== '' ? $title : 'Automatic user disable',
        );
        $poster = Staff::lookup(self::POSTER);
        if (!$poster)
            $poster = self::POSTER;      // plain poster name - no agent account needed

        $errors = array();
        // alert = false: no email goes out for this note
        if (!($entry = $ticket->postNote($vars, $errors, $poster, false)))
            return $this->exerr(500, 'Unable to add the note: ' . json_encode($errors));

        $this->debug('Ticket note API', sprintf('Note added to %s from %s', $number, $remote));

        Http::response(200, json_encode(array('ok' => true, 'note_id' => (int) $entry->getId())), 'application/json');
    }
}
