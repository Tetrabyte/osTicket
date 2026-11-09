<?php
/**
 * Tetrabyte plugin (2026-11-09): contact authorisation flags on the ticket page.
 *
 * The flags and the "authorised contacts" link that scp/UserSearch.php shows, drawn on
 * the ticket page: the flag icons at the end of the User row, and a small icon-only
 * button right after the organisation's ticket count.
 *
 * Why a plugin: no original osTicket file is edited at all, so nothing here can ever
 * conflict with an upstream merge. It listens for osTicket's own `object.view` signal
 * (sent as the ticket page starts) and writes a few lines of script into the page;
 * the script drops the pre-built HTML into the User and Organisation rows (osTicket
 * builds the rows after the signal fires, hence the script, not an echo in place).
 * What to show, and how to draw it, lives in include/tbyte.contactauth.php, which
 * User Search uses too.
 *
 * The plugin is switched on in the admin panel (Manage > Plugins > Add New Plugin >
 * Tetrabyte Contact Flags, then add an instance and set it to Enabled) and the two
 * parts can be turned off separately in the instance's settings.
 */
require_once INCLUDE_DIR.'class.plugin.php';
require_once INCLUDE_DIR.'class.forms.php';
require_once INCLUDE_DIR.'tbyte.contactauth.php';

class TbyteContactFlagsConfig extends PluginConfig {
    function getOptions() {
        return array(
            'show_flags' => new BooleanField(array(
                'label' => __('Show contact flags'),
                'default' => true,
                'configuration' => array(
                    'desc' => __('Flags next to the user: authorised for decisions / spending, important contact, user left')
                )
            )),
            'show_authorised_link' => new BooleanField(array(
                'label' => __('Show authorised contacts link'),
                'default' => true,
                'configuration' => array(
                    'desc' => __('Icon-only button next to the organisation, opening the company\'s authorised contacts')
                )
            )),
        );
    }
}

class TbyteContactFlagsPlugin extends Plugin {
    var $config_class = 'TbyteContactFlagsConfig';

    // One instance is all this needs.
    function isMultiInstance() {
        return false;
    }

    function bootstrap() {
        $config = $this->getConfig();
        $show = array(
            'flags'      => $config ? (bool) $config->get('show_flags', true) : true,
            'authorised' => $config ? (bool) $config->get('show_authorised_link', true) : true,
        );
        if (!$show['flags'] && !$show['authorised'])
            return;

        Signal::connect('object.view', function ($object, &$data) use ($show) {
            TbyteContactFlagsPlugin::decorateTicketPage($object, $show);
        });
    }

    /**
     * Called as an agent opens a ticket. Writes nothing at all unless the ticket's owner
     * has something to show, and can never stop the ticket page from loading.
     */
    static function decorateTicketPage($object, $show) {
        try {
            if (!defined('OSTSCPINC') || !($object instanceof Ticket)
                    || !($uid = (int) $object->getOwnerId()))
                return;

            $html = TbyteContactAuth::ticketPageHtml($uid);
            $flags = $show['flags'] ? $html['flags'] : '';
            $authorised = $show['authorised'] ? $html['authorised'] : '';
            if ($flags === '' && $authorised === '')
                return;

            $enc = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
            echo '<script type="text/javascript">'
               . '(function (uid, flagsHtml, authHtml) {'
               . 'var tries = 0;'
               . 'function place() {'
               . 'var nameEl = document.getElementById("user-" + uid + "-name");'
               . 'if (!nameEl) { if (tries++ < 20) setTimeout(place, 100); return; }'
               . 'var cell = nameEl.closest("td");'
               . 'if (flagsHtml && cell && !cell.querySelector(".tbyte-auth-flags"))'
               . ' cell.insertAdjacentHTML("beforeend", "<span class=\"tbyte-auth-flags\" style=\"margin-left:.5rem; white-space:nowrap;\">" + flagsHtml + "</span>");'
               . 'var org = document.querySelector("a[data-dropdown=\"#action-dropdown-org-stats\"]");'
               . 'if (authHtml && org && !org.parentNode.querySelector(".tbyte-auth-users"))'
               . ' org.insertAdjacentHTML("afterend", " " + authHtml);'
               . '}'
               . 'if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", place); else place();'
               . '})(' . $uid . ',' . json_encode($flags, $enc) . ',' . json_encode($authorised, $enc) . ');'
               . '</script>';
        } catch (Throwable $e) {
            // the ticket page matters more than the flags
        }
    }
}
