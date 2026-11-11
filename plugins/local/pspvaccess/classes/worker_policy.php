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

namespace local_pspvaccess;

/**
 * Decides whether a user is a worker of the hospital.
 *
 * Three rules can be switched on from the plugin settings: an account of the
 * hospital's Microsoft Entra ID tenant, a corporate email domain, and the
 * employee list imported from HR. Which ones apply, and whether one is enough
 * or all are needed, is the hospital's decision: with no rule configured nobody
 * is a worker.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class worker_policy {
    /** @var string Rule: the user signs in with the hospital's Microsoft tenant. */
    public const RULE_TENANT = 'tenant';

    /** @var string Rule: the user's email address is in a corporate domain. */
    public const RULE_DOMAIN = 'domain';

    /** @var string Rule: the user's email address is in the employee list. */
    public const RULE_LIST = 'list';

    /** @var string User preference with the time of the last login through the worker issuer. */
    public const PREF_TENANT_LOGIN = 'local_pspvaccess_tenantlogin';

    /**
     * The OAuth 2 issuer restricted to the hospital's tenant.
     *
     * @return int Issuer id, 0 when not configured.
     */
    public static function worker_issuer(): int {
        return (int) get_config('local_pspvaccess', 'workerissuer');
    }

    /**
     * The corporate email domains.
     *
     * @return string[] Lowercase domains, without "@".
     */
    public static function domains(): array {
        $domains = preg_split('/[\s,;]+/', \core_text::strtolower((string) get_config('local_pspvaccess', 'emaildomains')));
        $domains = array_map(fn($domain) => ltrim($domain, '@'), $domains);
        return array_values(array_unique(array_filter($domains, fn($domain) => $domain !== '')));
    }

    /**
     * The rules that are switched on.
     *
     * @return string[]
     */
    public static function enabled_rules(): array {
        $rules = [];
        if (self::worker_issuer()) {
            $rules[] = self::RULE_TENANT;
        }
        if (self::domains()) {
            $rules[] = self::RULE_DOMAIN;
        }
        if (get_config('local_pspvaccess', 'useemployeelist')) {
            $rules[] = self::RULE_LIST;
        }
        return $rules;
    }

    /**
     * The enabled rules a user meets.
     *
     * @param \stdClass $user User record.
     * @return string[]
     */
    public static function matching_rules(\stdClass $user): array {
        global $DB;

        if (
            empty($user->id) || isguestuser($user) || !empty($user->deleted) || !empty($user->suspended)
            || empty($user->confirmed)
        ) {
            return [];
        }
        $email = \core_text::strtolower(trim((string) $user->email));
        $matches = [];

        foreach (self::enabled_rules() as $rule) {
            if ($rule === self::RULE_TENANT) {
                // The link is only created after signing in to the tenant, and it is confirmed.
                $linked = $DB->record_exists_select(
                    'auth_oauth2_linked_login',
                    "userid = :userid AND issuerid = :issuerid AND (confirmtoken IS NULL OR confirmtoken = :empty)",
                    ['userid' => $user->id, 'issuerid' => self::worker_issuer(), 'empty' => ''],
                );
                $maxage = (int) get_config('local_pspvaccess', 'tenantmaxage');
                $lastlogin = (int) get_user_preferences(self::PREF_TENANT_LOGIN, 0, $user->id);
                if ($linked && ($maxage <= 0 || $lastlogin >= time() - $maxage * DAYSECS)) {
                    $matches[] = $rule;
                }
            } else if ($rule === self::RULE_DOMAIN) {
                $at = strrpos($email, '@');
                if ($at !== false && in_array(substr($email, $at + 1), self::domains(), true)) {
                    $matches[] = $rule;
                }
            } else if ($rule === self::RULE_LIST) {
                if ($email !== '' && $DB->record_exists('local_pspvaccess_employee', ['email' => $email])) {
                    $matches[] = $rule;
                }
            }
        }
        return $matches;
    }

    /**
     * Whether a user is a worker.
     *
     * @param \stdClass $user User record.
     * @return bool
     */
    public static function is_worker(\stdClass $user): bool {
        $enabled = self::enabled_rules();
        if (!$enabled) {
            return false;
        }
        $matches = self::matching_rules($user);
        if (get_config('local_pspvaccess', 'rulemode') === 'all') {
            return count($matches) === count($enabled);
        }
        return !empty($matches);
    }
}
