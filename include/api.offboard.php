<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';
include_once INCLUDE_DIR.'class.organization.php';

/**
 * Tetrabyte addition (2026-11-13): POST /api/offboard/emails.json
 *
 * Client offboarding, step "prefix the client's ticket-system email addresses with OLD_" (Tbyte
 * Portal: client/offboard.php). When a client leaves, every email address of every user in their
 * organisation(s) is renamed from  name@example.com  to  OLD_name@example.com . The tickets, users
 * and organisation stay (they are kept for a retention period and removed later); mail from the
 * original addresses no longer matches those users, and the portal's contacts sync skips OLD_
 * addresses. Done through the UserEmail model (the same one osTicket saves addresses with), not by
 * writing to the database directly.
 *
 * Request  (JSON, X-API-Key header):
 *   {"org_ids": [12, 40], "dry_run": false}
 * Response (JSON):
 *   {"summary": {"orgs": n, "orgs_missing": [ids], "users": n, "emails_renamed": n,
 *                "emails_would_rename": n, "already_prefixed": n, "skipped_too_long": n,
 *                "users_with_login_account": n}}
 *
 * Idempotent: addresses that already start with OLD_ are left alone, so a repeat (or a retry after a
 * failure part-way) is safe. Nothing is deleted. Same key rules as /api/users.json: the key must be
 * active, allowed to create tickets, and registered to the IP the request comes from.
 */
class OffboardApiController extends ApiController {

    const MAX_ORGS = 20;
    const PREFIX = 'OLD_';

    function emails($format = 'json') {
        if (!($key = $this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        $registered = trim((string) $key->getIPAddr());
        $remote = (string) $this->getRemoteAddr();
        if ($registered === '' || $registered !== $remote)
            return $this->exerr(401, sprintf('API key is not registered for this source address (%s)', $remote));

        $data = $this->getRequest('json', false);
        $org_ids = array();
        if (is_array($data) && isset($data['org_ids']) && is_array($data['org_ids'])) {
            foreach ($data['org_ids'] as $id) {
                if ((int) $id > 0)
                    $org_ids[(int) $id] = (int) $id;
            }
        }
        if (!$org_ids)
            return $this->exerr(400, 'No organisations supplied');
        if (count($org_ids) > self::MAX_ORGS)
            return $this->exerr(400, sprintf('At most %d organisations per request', self::MAX_ORGS));
        $dry_run = !empty($data['dry_run']);

        $summary = array('orgs' => 0, 'orgs_missing' => array(), 'users' => 0, 'emails_renamed' => 0,
            'emails_would_rename' => 0, 'already_prefixed' => 0, 'skipped_too_long' => 0,
            'users_with_login_account' => 0);

        foreach ($org_ids as $org_id) {
            if (!($org = Organization::lookup($org_id))) {
                $summary['orgs_missing'][] = $org_id;
                continue;
            }
            $summary['orgs']++;

            // Same filter osTicket's own Organization::delete() uses to find an org's users.
            foreach (User::objects()->filter(array('org' => $org)) as $user) {
                $summary['users']++;
                if ($user->account)
                    $summary['users_with_login_account']++;

                foreach ($user->emails as $email) {
                    $address = (string) $email->address;
                    if (stripos($address, self::PREFIX) === 0) {
                        $summary['already_prefixed']++;
                    }
                    elseif (strlen(self::PREFIX . $address) > 255) {
                        $summary['skipped_too_long']++;
                    }
                    elseif ($dry_run) {
                        $summary['emails_would_rename']++;
                    }
                    else {
                        $email->address = self::PREFIX . $address;
                        $email->save();
                        $summary['emails_renamed']++;
                    }
                }
            }
        }

        $this->debug('Offboard API', sprintf('%s%d organisation(s) from %s: %s',
            $dry_run ? '[dry run] ' : '', count($org_ids), $this->getRemoteAddr(), json_encode($summary)));

        Http::response(200, json_encode(array('summary' => $summary)), 'application/json');
    }
}
