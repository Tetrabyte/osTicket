<?php
/**
 * Tetrabyte addition (2026-11-09): contact "authorisation" flags and the link to a
 * company's authorised contacts.
 *
 * ONE place that knows how to read and draw them, used by
 *   - scp/UserSearch.php                          (the User Search page), and
 *   - include/plugins/tbyte-contact-flags/        (the same flags + link on the ticket page)
 * so both always show exactly the same thing.
 *
 * Source of truth: the portal's `tbyte-portal`.clients_contacts (one row per
 * ticket-system user, kept in step by the portal's contact sync), read through
 * osTicket's own database connection (same server, so a plain cross-database SELECT).
 * Nothing here writes anything.
 *
 * Self-contained on purpose (standing order: Tetrabyte changes live in new files, the
 * original osTicket files stay as close to upstream as possible).
 */
class TbyteContactAuth {

    const PORTAL_URL = 'https://portal.remoteit.co.uk';

    /** Rows already read during this request, by ticket user id (null = no row). */
    private static $rows = array();

    /**
     * The three "this person is authorised for ..." flags: column, tooltip, colour and
     * the icon in each of the two icon sets (Font Awesome 6 on User Search, Bootstrap
     * Icons on the ticket page).
     */
    static function flagDefs() {
        return array(
            'contact_decisions' => array(
                'title'  => 'Authorised for Decision Making',
                'colour' => 'blue',
                'fa'     => 'fa-solid fa-circle-check',
                'bi'     => 'bi bi-check-circle-fill',
            ),
            'contact_spending' => array(
                'title'  => 'Authorised for Spending',
                'colour' => 'purple',
                'fa'     => 'fa-solid fa-sterling-sign',
                'bi'     => 'bi bi-currency-pound',
            ),
            'contact_important' => array(
                'title'  => 'Important Contact',
                'colour' => 'orange',
                'fa'     => 'fa-solid fa-star',
                'bi'     => 'bi bi-star-fill',
            ),
        );
    }

    /**
     * The portal's contact row for a ticket-system user id, or null when the portal has
     * none (or the portal database can't be read - the caller just shows nothing).
     */
    static function lookup($userId) {
        $userId = (int) $userId;
        if ($userId <= 0)
            return null;
        if (!array_key_exists($userId, self::$rows)) {
            $row = null;
            try {
                $res = db_query(
                    "SELECT client_id, contact_decisions, contact_spending, contact_important, contact_gone,
                            contact_authorisation_notes, contact_authorisation_url
                       FROM `tbyte-portal`.clients_contacts
                      WHERE ticket_user_id = $userId",
                    true, true);
                $row = $res ? $res->fetch_assoc() : null;
            } catch (Throwable $e) {
                $row = null;
            }
            self::$rows[$userId] = $row ?: null;
        }
        return self::$rows[$userId];
    }

    static function hasFlag($row) {
        if (!$row)
            return false;
        foreach (array_keys(self::flagDefs()) as $col)
            if (!empty($row[$col]))
                return true;
        return !empty($row['contact_gone']);
    }

    /** The portal client this user belongs to, or null. */
    static function clientId($userId) {
        $row = self::lookup($userId);
        return ($row && $row['client_id']) ? (int) $row['client_id'] : null;
    }

    /** Portal page for this one contact (where the flags and the notes are edited). */
    static function contactUrl($userId) {
        $userId = (int) $userId;
        $url = self::PORTAL_URL . '/client/client_contacts.php?ticket_user_id=' . $userId;
        if ($clientId = self::clientId($userId))
            $url .= '&client_id=' . $clientId;
        return $url;
    }

    /** Portal page listing every authorised contact of this user's company, or null. */
    static function authorisedUrl($userId) {
        $clientId = self::clientId($userId);
        return $clientId
            ? self::PORTAL_URL . '/client/client_contacts.php?client_id=' . $clientId . '&view=authorised'
            : null;
    }

    /**
     * The authorisation note as plain text, already escaped for an HTML attribute / text
     * (the note itself is cleaned HTML written on the portal's contact page).
     */
    static function plainNotes($row) {
        $raw = $row ? trim((string) $row['contact_authorisation_notes']) : '';
        return htmlspecialchars(strip_tags($raw), ENT_QUOTES);
    }

    /**
     * The icons for the flags this user has, each linking to their portal contact page.
     * $o: icons ('fa'|'bi'), size (CSS font-size), link_class, notes_in_title (append the
     * authorisation note to each tooltip). Returns '' when the user has none of them.
     */
    static function flagLinksHtml($row, $href, $o = array()) {
        $o += array('icons' => 'fa', 'size' => '1.5em', 'link_class' => '', 'notes_in_title' => false);
        if (!$row)
            return '';
        $notes = $o['notes_in_title'] ? self::plainNotes($row) : '';
        $class = $o['link_class'] ? ' class="'.htmlspecialchars($o['link_class'], ENT_QUOTES).'"' : '';
        $set = ($o['icons'] === 'bi') ? 'bi' : 'fa';
        $html = '';
        foreach (self::flagDefs() as $col => $def) {
            if (empty($row[$col]))
                continue;
            $title = $def['title'] . ($notes !== '' ? ' - ' . $notes : '');
            $html .= '<a href="'.htmlspecialchars($href, ENT_QUOTES).'" target="_blank" rel="noopener"'.$class
                   . ' title="'.$title.'" style="color:'.$def['colour'].'; font-size:'.$o['size'].';">'
                   . '<i class="'.$def[$set].'"></i></a>&nbsp;';
        }
        return $html;
    }

    /** The red "User Disabled/Left" badge (tooltip = the authorisation note), or ''. */
    static function goneBadgeHtml($row, $href, $o = array()) {
        $o += array('size' => '1em', 'link_class' => '');
        if (!$row || empty($row['contact_gone']))
            return '';
        $class = $o['link_class'] ? ' class="'.htmlspecialchars($o['link_class'], ENT_QUOTES).'"' : '';
        return '<a href="'.htmlspecialchars($href, ENT_QUOTES).'" target="_blank" rel="noopener"'.$class
             . ' title="'.self::plainNotes($row).'"><span class="badge bg-danger" style="font-size:'.$o['size'].';">'
             . 'User Disabled/Left</span></a>';
    }

    /** Small icon-only button linking to every authorised contact of the user's company. */
    static function authorisedButtonHtml($url) {
        return '<a class="btn btn-secondary btn-sm no-pjax tbyte-auth-users" target="_blank" rel="noopener"'
             . ' href="'.htmlspecialchars($url, ENT_QUOTES).'" title="Authorised contacts for this company"'
             . ' style="--bs-btn-padding-y: .25rem !important; --bs-btn-padding-x: .5rem !important;">'
             . '<i class="bi bi-people-fill"></i></a>';
    }

    /**
     * Everything the ticket page needs for the ticket's owner:
     *   flags      - the flag icons (+ "User Disabled/Left"), '' if they have none
     *   authorised - the icon-only "authorised contacts" button, '' if the portal has no client for them
     */
    static function ticketPageHtml($userId) {
        $out = array('flags' => '', 'authorised' => '');
        try {
            if (!($row = self::lookup($userId)))
                return $out;
            if (self::hasFlag($row)) {
                $href = self::contactUrl($userId);
                $o = array('icons' => 'bi', 'size' => '1.2em', 'link_class' => 'no-pjax', 'notes_in_title' => true);
                $out['flags'] = self::flagLinksHtml($row, $href, $o)
                              . self::goneBadgeHtml($row, $href, array('size' => '.85em', 'link_class' => 'no-pjax'));
            }
            if ($url = self::authorisedUrl($userId))
                $out['authorised'] = self::authorisedButtonHtml($url);
        } catch (Throwable $e) {
            // never let this break the page it decorates
        }
        return $out;
    }
}
