<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';
include_once INCLUDE_DIR.'class.organization.php';

/**
 * Tetrabyte addition (2026-10-16): POST /api/users.json
 *
 * Lets the Tbyte Portal add ticket-system users in bulk (its M365 -> osTicket
 * user sync cron) WITHOUT writing to this database directly. Every user is
 * created through User::fromVars() - the same call osTicket makes when an
 * unknown address emails in or opens a ticket - so everything osTicket itself
 * writes for a new user is written here too, and the signals it raises fire:
 *   ost_user_email  (the address)           ost_user  (name, org_id, default email)
 *   ost_form_entry + ost_form_entry_values  (the Contact Information form)
 *   ost_user__cdata (cached form data)      ost__search (the global-search index,
 *                                            via the user.created signal)
 * No ost_user_account is created (the person is a contact, not a registered
 * portal account) and no email is sent.
 *
 * Request  (JSON, X-API-Key header):
 *   {"dry_run": false, "users": [{"email": "a@b.com", "name": "First Last", "org_id": 12}, ...]}
 * Response (JSON):
 *   {"summary": {"created": n, "exists": n, ...}, "results": [{"email", "status", "user_id"?, "error"?}, ...]}
 * status: created | would_create (dry run) | exists | invalid_email | org_missing | error
 *
 * Existing users are NEVER modified or moved to another organisation. The key
 * must be active, allowed to create tickets, and registered to the IP the request
 * comes from (checked here: this fork's ApiController::getKey() does not).
 */
class UserApiController extends ApiController {

    const MAX_BATCH = 100;

    function sync($format = 'json') {
        if (!($key = $this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        // The key table stores the IP each key was issued for; getKey() above
        // looks keys up without it, so enforce it here for this write endpoint.
        $registered = trim((string) $key->getIPAddr());
        $remote = (string) $this->getRemoteAddr();
        if ($registered === '' || $registered !== $remote)
            return $this->exerr(401, sprintf('API key is not registered for this source address (%s)', $remote));

        $data = $this->getRequest('json', false);
        $users = (is_array($data) && isset($data['users']) && is_array($data['users'])) ? $data['users'] : null;
        if (!$users)
            return $this->exerr(400, 'No users supplied');
        if (count($users) > self::MAX_BATCH)
            return $this->exerr(400, sprintf('At most %d users per request', self::MAX_BATCH));
        $dry_run = !empty($data['dry_run']);

        $results = array();
        $summary = array();
        foreach ($users as $item) {
            $email = strtolower(trim((string) (is_array($item) ? ($item['email'] ?? '') : '')));
            $name = trim((string) (is_array($item) ? ($item['name'] ?? '') : ''));
            $org_id = (int) (is_array($item) ? ($item['org_id'] ?? 0) : 0);
            $r = array('email' => $email);

            try {
                if (!$email || !Validator::is_email($email)) {
                    $r['status'] = 'invalid_email';
                    $r['error'] = 'Not a valid email address';
                }
                elseif (!$org_id || !Organization::lookup($org_id)) {
                    $r['status'] = 'org_missing';
                    $r['error'] = 'Organization not found';
                }
                elseif (($existing = User::lookupByEmail($email))) {
                    $r['status'] = 'exists';
                    $r['user_id'] = (int) $existing->getId();
                }
                elseif ($dry_run) {
                    $r['status'] = 'would_create';
                }
                else {
                    $user = User::fromVars(array(
                        'email'  => $email,
                        'name'   => $name,
                        'org_id' => $org_id,
                    ), true, false);
                    if ($user && $user->getId()) {
                        $r['status'] = 'created';
                        $r['user_id'] = (int) $user->getId();
                    } else {
                        $r['status'] = 'error';
                        $r['error'] = 'osTicket did not create the user';
                    }
                }
            } catch (Throwable $e) {
                $r['status'] = 'error';
                $r['error'] = $e->getMessage();
            }

            $summary[$r['status']] = ($summary[$r['status']] ?? 0) + 1;
            $results[] = $r;
        }

        $this->debug('User API', sprintf('%s%d user(s) from %s: %s',
            $dry_run ? '[dry run] ' : '', count($users), $this->getRemoteAddr(), json_encode($summary)));

        Http::response(200, json_encode(array('summary' => $summary, 'results' => $results)), 'application/json');
    }
}
