<?php
/**
 * Tetrabyte: the buttons after the organisation's ticket count on the ticket page:
 *   - an icon-only link to the company's authorised contacts (same data as User Search),
 *   - Create Quote, and
 *   - one button per quote already raised for this ticket (portal quotes-by-ticket API).
 *
 * Included, with one line, from the Organization row of include/staff/ticket-view.inc.php,
 * so it runs in that template's scope ($ticket, $user). The Create Quote / quote-buttons
 * code below was moved here unchanged from that template.
 */
if (!defined('OSTSCPINC') || !isset($ticket, $user))
    return;

require_once INCLUDE_DIR.'tbyte.contactauth.php';

if ($__tbyteAuthButton = TbyteContactAuth::ticketAuthorisedButtonHtml($ticket->getOwnerId()))
    echo ' '.$__tbyteAuthButton."\n";
?>
                        <a class='btn btn-sm no-pjax' target="_blank" href='https://portal.remoteit.co.uk/quotes/new.php?orgid=<?php echo $user->getOrgId(); ?>&ticket=<?php echo $ticket->getNumber(); ?>' style="--bs-btn-padding-y: .25rem !important; --bs-btn-padding-x: .5rem !important; background-color:#6F42C1; border-color:#6F42C1; color:#fff;">
                        <i class="bi bi-cart4"></i> <?php echo __('Create Quote'); ?>
                        </a>
                        <?php
                        // Existing quotes for this ticket, via the Tbyte Portal quotes-by-ticket API.
                        // Auth token read from the gitignored .env file at the site root.
                        $__quoteApiToken = null;
                        $__envPath = ROOT_DIR.'.env';
                        if (is_file($__envPath)) {
                            foreach (file($__envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__envLine) {
                                if (strpos(trim($__envLine), '#') === 0 || strpos($__envLine, '=') === false)
                                    continue;
                                list($__envKey, $__envValue) = array_map('trim', explode('=', $__envLine, 2));
                                if ($__envKey === 'QUOTES_API_AUTH_TOKEN')
                                    $__quoteApiToken = $__envValue;
                            }
                        }
                        $__ticketQuotes = array();
                        if ($__quoteApiToken) {
                            $__quoteApiUrl = 'https://portal.remoteit.co.uk/quotes/api-by-ticket.php?ticket=' . urlencode($ticket->getNumber());
                            $__quoteApiContext = stream_context_create(array(
                                'http' => array(
                                    'method' => 'GET',
                                    'header' => "Auth: $__quoteApiToken",
                                    'timeout' => 5,
                                    'ignore_errors' => true,
                                )
                            ));
                            $__quoteApiResponse = @file_get_contents($__quoteApiUrl, false, $__quoteApiContext);
                            if ($__quoteApiResponse !== false) {
                                $__quoteApiData = json_decode($__quoteApiResponse, true);
                                if (is_array($__quoteApiData) && isset($__quoteApiData['quotes']) && is_array($__quoteApiData['quotes']))
                                    $__ticketQuotes = $__quoteApiData['quotes'];
                            }
                        }
                        foreach ($__ticketQuotes as $__quote) {
                            $__quoteStatus = strtolower(trim((string) ($__quote['status'] ?? '')));
                            if (in_array($__quoteStatus, array('cancelled', 'rejected'))) {
                                $__quoteColor = '#DC3545'; $__quoteText = '#fff';
                            } elseif (in_array($__quoteStatus, array('accepted', 'invoiced', 'complete'))) {
                                $__quoteColor = '#198754'; $__quoteText = '#fff';
                            } else {
                                $__quoteColor = '#0DCAF0'; $__quoteText = '#000';
                            }
                        ?>
                        <a class='btn btn-sm no-pjax' target="_blank"
                            href='<?php echo Format::htmlchars($__quote['url']); ?>'
                            title='<?php echo Format::htmlchars((string) $__quote['title']); ?>'
                            style="--bs-btn-padding-y: .25rem !important; --bs-btn-padding-x: .5rem !important; background-color:<?php echo $__quoteColor; ?>; border-color:<?php echo $__quoteColor; ?>; color:<?php echo $__quoteText; ?>;">
                        <i class="bi bi-receipt"></i> <?php echo Format::htmlchars($__quote['quote_number']); ?>
                        </a>
                        <?php } ?>
