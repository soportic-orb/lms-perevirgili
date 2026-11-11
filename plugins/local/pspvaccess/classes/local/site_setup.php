<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_pspvaccess\local;

use core\oauth2\issuer;
use tool_policy\policy_version;

/**
 * Site configuration that depends on values kept outside the database: the
 * OAuth 2 applications (their client ids and secrets come from config.php) and
 * the privacy tools.
 *
 * Every step is idempotent. Nothing is invented: a provider without credentials
 * is not created, and the policy documents are created as drafts with
 * placeholder text for the hospital to replace.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_setup {
    /**
     * Switches on OAuth 2 logins and self-registration by email, and opens the site to visitors.
     *
     * @return string[] Lines describing what was done.
     */
    public static function configure_authentication(): array {
        global $CFG;

        $lines = [];
        if (!is_enabled_auth('oauth2')) {
            \core\plugininfo\auth::enable_plugin('oauth2', 1);
            $lines[] = 'OAuth 2 authentication enabled.';
        }
        // External users can also register with an email address and a password.
        if (($CFG->registerauth ?? '') !== 'email') {
            set_config('registerauth', 'email');
            $lines[] = 'Email-based self-registration enabled.';
        }
        // The external catalogue is public: new installations force everybody to log in.
        if (!empty($CFG->forcelogin)) {
            set_config('forcelogin', 0);
            $lines[] = 'Visitors can browse the site without logging in.';
        }
        return $lines ?: ['Authentication already configured.'];
    }

    /**
     * Creates or updates the Google and Microsoft issuers from config.php.
     *
     * Microsoft gets two issuers on the same application: one open to any account,
     * for external users, and one restricted to the hospital's tenant. Signing in
     * through the second one is what the "tenant" worker rule checks.
     *
     * @return string[] Lines describing what was done.
     */
    public static function configure_issuers(): array {
        $lines = [];
        $config = get_config('local_pspvaccess');

        $lines[] = self::ensure_issuer(
            'issuer_google',
            'google',
            'Google',
            null,
            (string) ($config->googleclientid ?? ''),
            (string) ($config->googleclientsecret ?? ''),
        );
        $microsoftid = (string) ($config->microsoftclientid ?? '');
        $microsoftsecret = (string) ($config->microsoftclientsecret ?? '');
        $lines[] = self::ensure_issuer('issuer_microsoft', 'microsoft', 'Microsoft', null, $microsoftid, $microsoftsecret);

        $tenant = trim((string) ($config->microsofttenant ?? ''));
        if ($tenant !== '' && !preg_match('/^[A-Za-z0-9.-]+$/', $tenant)) {
            $lines[] = 'Microsoft (workers): the tenant must be a tenant id or a verified domain; not created.';
        } else {
            $lines[] = self::ensure_issuer(
                'issuer_worker',
                'microsoft',
                self::name('issuer_worker'),
                $tenant === '' ? '' : 'https://login.microsoftonline.com/' . $tenant . '/v2.0',
                $microsoftid,
                $microsoftsecret,
            );
            $workerissuer = (int) get_config('local_pspvaccess', 'issuer_worker');
            if ($workerissuer && !get_config('local_pspvaccess', 'workerissuer')) {
                set_config('workerissuer', $workerissuer, 'local_pspvaccess');
                $lines[] = 'Worker rule "tenant" now uses the workers issuer.';
            }
        }
        return $lines;
    }

    /**
     * Makes tool_policy the policy handler and creates the draft policy documents.
     *
     * @return string[] Lines describing what was done.
     */
    public static function configure_privacy(): array {
        global $CFG;

        $lines = [];
        if (($CFG->sitepolicyhandler ?? '') !== 'tool_policy') {
            set_config('sitepolicyhandler', 'tool_policy');
            $lines[] = 'Policies are now handled by tool_policy.';
        }
        if (!get_config('tool_dataprivacy', 'contactdataprotectionofficer')) {
            set_config('contactdataprotectionofficer', 1, 'tool_dataprivacy');
            $lines[] = 'Users can contact the data protection officer from their profile.';
        }

        $drafts = [
            'policy_privacy' => [policy_version::TYPE_PRIVACY, 0],
            'policy_terms' => [policy_version::TYPE_SITE, 0],
            // Optional consent: local_pspvmailing only writes to those who accept it.
            'policy_marketing' => [policy_version::TYPE_OTHER, 1],
        ];
        foreach ($drafts as $key => [$type, $optional]) {
            if (get_config('local_pspvaccess', $key)) {
                continue;
            }
            $placeholder = ['text' => self::name('policy_placeholder'), 'format' => FORMAT_HTML, 'itemid' => 0];
            $version = \tool_policy\api::form_policydoc_add((object) [
                'name' => self::name($key),
                'type' => $type,
                'audience' => policy_version::AUDIENCE_ALL,
                'revision' => self::name('policy_revision'),
                'summary_editor' => $placeholder,
                'content_editor' => $placeholder,
                'agreementstyle' => policy_version::AGREEMENTSTYLE_CONSENTPAGE,
                'optional' => $optional,
            ]);
            set_config($key, $version->get('policyid'), 'local_pspvaccess');
            $lines[] = 'Draft policy created: ' . self::name($key);
        }
        return $lines ?: ['Privacy tools already configured.'];
    }

    /**
     * Creates or updates one issuer.
     *
     * @param string $key Plugin setting that remembers the issuer id.
     * @param string $type Standard issuer type: "google" or "microsoft".
     * @param string $name Name of the issuer.
     * @param string|null $baseurl Base URL; null for the default of the type, '' when it is not configured.
     * @param string $clientid
     * @param string $clientsecret
     * @return string Line describing what was done.
     */
    protected static function ensure_issuer(
        string $key,
        string $type,
        string $name,
        ?string $baseurl,
        string $clientid,
        string $clientsecret,
    ): string {
        if ($clientid === '' || $clientsecret === '' || $baseurl === '') {
            return "$name: no credentials in .env, not configured.";
        }

        $issuerid = (int) get_config('local_pspvaccess', $key);
        $issuer = $issuerid ? issuer::get_record(['id' => $issuerid]) : false;
        if (!$issuer) {
            try {
                $issuer = \core\oauth2\api::create_standard_issuer($type, $baseurl ?? false);
            } catch (\Throwable $e) {
                // The endpoints are discovered online; a half-created issuer would be useless.
                foreach (issuer::get_records(['clientid' => '', 'servicetype' => $type]) as $orphan) {
                    if (!\core\oauth2\api::get_endpoints($orphan)) {
                        \core\oauth2\api::delete_issuer($orphan->get('id'));
                    }
                }
                return "$name: could not be created (" . trim(strip_tags($e->getMessage())) . ').';
            }
            set_config($key, $issuer->get('id'), 'local_pspvaccess');
            $action = 'created';
        } else if ($issuer->get('clientid') === $clientid && $issuer->get('clientsecret') === $clientsecret) {
            return "$name: already configured.";
        } else {
            $action = 'credentials updated';
        }

        $issuer->set('name', $name);
        $issuer->set('clientid', $clientid);
        $issuer->set('clientsecret', $clientsecret);
        $issuer->set('enabled', 1);
        $issuer->set('showonloginpage', issuer::LOGINONLY);
        // New accounts confirm their email address, so an address cannot be claimed without owning it.
        $issuer->set('requireconfirmation', 1);
        $issuer->update();
        return "$name: $action.";
    }

    /**
     * A name in the default language of the site, to store in the database.
     *
     * @param string $identifier String identifier.
     * @return string
     */
    protected static function name(string $identifier): string {
        global $CFG;

        return get_string_manager()->get_string($identifier, 'local_pspvaccess', null, $CFG->lang ?? 'en');
    }
}
