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

namespace local_pspvmailing;

/**
 * Selects the recipients of a campaign.
 *
 * A segment narrows the users down by cohort, course and purchases. On top of that, a commercial
 * campaign only reaches who has not opted out and has accepted the policy on commercial
 * communications; workers of the hospital can be exempted from that consent in the settings.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class segment {
    /** @var string Purchase criterion: who has paid an order. */
    public const BUYERS = 'buyers';

    /** @var string Purchase criterion: who has never paid an order. */
    public const NONBUYERS = 'nonbuyers';

    /**
     * Cleans the criteria of a segment.
     *
     * @param array|null $criteria
     * @return array cohortid, courseid and purchase.
     */
    public static function normalise(?array $criteria): array {
        $purchase = (string) ($criteria['purchase'] ?? '');
        return [
            'cohortid' => max(0, (int) ($criteria['cohortid'] ?? 0)),
            'courseid' => max(0, (int) ($criteria['courseid'] ?? 0)),
            'purchase' => in_array($purchase, [self::BUYERS, self::NONBUYERS], true) && self::shop_installed() ? $purchase : '',
        ];
    }

    /**
     * Whether the shop is installed, which is what makes the purchase criterion possible.
     *
     * @return bool
     */
    public static function shop_installed(): bool {
        return (bool) get_config('local_pspvshop', 'version');
    }

    /**
     * Id of the tool_policy document users accept to receive commercial communications.
     *
     * @return int 0 when none is set.
     */
    public static function commercial_policy(): int {
        $own = get_config('local_pspvmailing', 'commercialpolicy');
        if ($own !== false && $own !== '') {
            return (int) $own;
        }
        // The optional policy that local_pspvaccess creates for this purpose.
        return (int) get_config('local_pspvaccess', 'policy_marketing');
    }

    /**
     * Id of the cohort of workers exempted from the consent, 0 when they are not exempted.
     *
     * @return int
     */
    public static function exempt_cohort(): int {
        global $DB;

        $always = get_config('local_pspvmailing', 'workersalways');
        if ($always !== false && empty($always)) {
            return 0;
        }
        $idnumber = get_config('local_pspvmailing', 'workercohort');
        $idnumber = $idnumber === false ? 'pspv-workers' : trim((string) $idnumber);
        return $idnumber === '' ? 0 : (int) $DB->get_field('cohort', 'id', ['idnumber' => $idnumber]);
    }

    /**
     * SQL that selects the ids of the recipients.
     *
     * @param array $criteria
     * @param bool $commercial
     * @return array [string sql selecting u.id, array params]
     */
    protected static function sql(array $criteria, bool $commercial): array {
        global $CFG, $DB;

        $criteria = self::normalise($criteria);
        $where = [
            'u.deleted = 0', 'u.suspended = 0', 'u.confirmed = 1', 'u.emailstop = 0', "u.email <> ''", 'u.id <> :guestid',
        ];
        $params = ['guestid' => $CFG->siteguest];
        $joins = '';

        if ($criteria['cohortid']) {
            $joins .= ' JOIN {cohort_members} segcm ON segcm.userid = u.id AND segcm.cohortid = :cohortid';
            $params['cohortid'] = $criteria['cohortid'];
        }
        if ($criteria['courseid']) {
            $context = \core\context\course::instance($criteria['courseid'], IGNORE_MISSING);
            if (!$context) {
                return ['SELECT u.id FROM {user} u WHERE 1 = 0', []];
            }
            [$enrolledsql, $enrolledparams] = get_enrolled_sql($context, '', 0, true);
            $joins .= " JOIN ($enrolledsql) segen ON segen.id = u.id";
            $params += $enrolledparams;
        }
        if ($criteria['purchase'] !== '') {
            $not = $criteria['purchase'] === self::NONBUYERS ? 'NOT ' : '';
            $where[] = $not . "EXISTS (SELECT 1
                                         FROM {local_pspvshop_order} sego
                                        WHERE sego.userid = u.id AND sego.status = 'paid')";
        }
        if ($commercial) {
            $where[] = 'NOT EXISTS (SELECT 1 FROM {local_pspvmailing_optout} sego2 WHERE sego2.userid = u.id)';
            $consent = [];
            if ($policyid = self::commercial_policy()) {
                $consent[] = "EXISTS (SELECT 1
                                        FROM {tool_policy_acceptances} sega
                                        JOIN {tool_policy} segp ON segp.currentversionid = sega.policyversionid
                                       WHERE sega.userid = u.id AND sega.status = 1 AND segp.id = :policyid)";
                $params['policyid'] = $policyid;
            }
            if ($cohortid = self::exempt_cohort()) {
                $consent[] = 'EXISTS (SELECT 1 FROM {cohort_members} segw WHERE segw.userid = u.id AND segw.cohortid = :workers)';
                $params['workers'] = $cohortid;
            }
            // Without a policy to accept and without exempted workers, nobody has given consent.
            $where[] = $consent ? '(' . implode(' OR ', $consent) . ')' : '1 = 0';
        }
        return ["SELECT u.id FROM {user} u $joins WHERE " . implode(' AND ', $where), $params];
    }

    /**
     * Ids of the recipients.
     *
     * @param array $criteria
     * @param bool $commercial
     * @return int[]
     */
    public static function user_ids(array $criteria, bool $commercial): array {
        global $DB;

        [$sql, $params] = self::sql($criteria, $commercial);
        return array_map('intval', $DB->get_fieldset_sql($sql . ' ORDER BY u.id', $params));
    }

    /**
     * Number of recipients.
     *
     * @param array $criteria
     * @param bool $commercial
     * @return int
     */
    public static function count(array $criteria, bool $commercial): int {
        global $DB;

        [$sql, $params] = self::sql($criteria, $commercial);
        return $DB->count_records_sql("SELECT COUNT(1) FROM ($sql) segcount", $params);
    }

    /**
     * Describes a segment in words.
     *
     * @param array $criteria
     * @return string[]
     */
    public static function describe(array $criteria): array {
        global $DB;

        $criteria = self::normalise($criteria);
        $lines = [];
        if ($criteria['cohortid']) {
            $name = $DB->get_field('cohort', 'name', ['id' => $criteria['cohortid']]);
            $lines[] = get_string('segment_in_cohort', 'local_pspvmailing', $name === false ? '?' : format_string($name));
        }
        if ($criteria['courseid']) {
            $name = $DB->get_field('course', 'fullname', ['id' => $criteria['courseid']]);
            $lines[] = get_string('segment_in_course', 'local_pspvmailing', $name === false ? '?' : format_string($name));
        }
        if ($criteria['purchase'] !== '') {
            $lines[] = get_string('segment_purchase_' . $criteria['purchase'], 'local_pspvmailing');
        }
        return $lines ?: [get_string('segment_everyone', 'local_pspvmailing')];
    }
}
