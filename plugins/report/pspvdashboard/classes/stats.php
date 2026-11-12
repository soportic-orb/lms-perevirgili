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

namespace report_pspvdashboard;

/**
 * The figures of the dashboard.
 *
 * Sales come from local_pspvshop, accreditations from local_pspvaccredit and the service and
 * job category of the workers from the staff list of local_pspvaccess. Each of those parts is
 * simply left out when its plugin is not installed.
 *
 * @package    report_pspvdashboard
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stats {
    /** @var string Idnumber of the internal training category created by local_pspvaccess. */
    public const INTERNAL_CATEGORY = 'pspv-internal';

    /** @var string[] Columns of the staff list the internal training can be broken down by. */
    public const DIMENSIONS = ['department', 'jobcategory'];

    /** @var int Start of the period, 0 for no limit. */
    protected int $from;

    /** @var int End of the period, 0 for no limit. */
    protected int $to;

    /**
     * Constructor.
     *
     * @param int $from Start of the period, 0 for no limit.
     * @param int $to End of the period, 0 for no limit.
     */
    public function __construct(int $from = 0, int $to = 0) {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Whether the shop is installed.
     *
     * @return bool
     */
    public static function has_sales(): bool {
        return (bool) get_config('local_pspvshop', 'version');
    }

    /**
     * Whether the accreditation plugin is installed.
     *
     * @return bool
     */
    public static function has_accreditation(): bool {
        return (bool) get_config('local_pspvaccredit', 'version');
    }

    /**
     * Whether the staff list is available.
     *
     * @return bool
     */
    public static function has_staff_list(): bool {
        return (bool) get_config('local_pspvaccess', 'version');
    }

    /**
     * SQL condition that keeps a time column inside the period.
     *
     * @param string $column
     * @param string $prefix Unique prefix for the parameter names.
     * @return array [string sql starting with " AND", or empty; array params]
     */
    protected function period(string $column, string $prefix): array {
        $sql = '';
        $params = [];
        if ($this->from) {
            $sql .= " AND $column >= :{$prefix}from";
            $params[$prefix . 'from'] = $this->from;
        }
        if ($this->to) {
            $sql .= " AND $column <= :{$prefix}to";
            $params[$prefix . 'to'] = $this->to;
        }
        return [$sql, $params];
    }

    /**
     * Totals of the period.
     *
     * @return array enrolments, completions, activeusers and, when their plugins are installed,
     *               orders, revenue (float), currency and accreditations.
     */
    public function summary(): array {
        global $DB;

        [$period, $params] = $this->period('ue.timecreated', 'e');
        $enrolments = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
               JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
              WHERE e.courseid <> :siteid $period",
            $params + ['siteid' => SITEID],
        );
        [$period, $params] = $this->period('cc.timecompleted', 'c');
        $completions = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {course_completions} cc
               JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
              WHERE cc.timecompleted > 0 $period",
            $params,
        );
        [$period, $params] = $this->period('u.lastaccess', 'a');
        $active = $DB->count_records_sql(
            "SELECT COUNT(1) FROM {user} u WHERE u.deleted = 0 AND u.lastaccess > 0 $period",
            $params,
        );
        $summary = ['enrolments' => $enrolments, 'completions' => $completions, 'activeusers' => $active];

        if (self::has_sales()) {
            [$period, $params] = $this->period('o.timepaid', 'o');
            $sales = $DB->get_record_sql(
                "SELECT COUNT(1) AS orders, COALESCE(SUM(o.total), 0) AS revenue, MAX(o.currency) AS currency
                   FROM {local_pspvshop_order} o
                  WHERE o.status = 'paid' $period",
                $params,
            );
            $summary['orders'] = (int) $sales->orders;
            $summary['revenue'] = (float) $sales->revenue;
            $summary['currency'] = (string) ($sales->currency ?: get_config('local_pspvshop', 'currency') ?: 'EUR');
        }
        if (self::has_accreditation()) {
            [$period, $params] = $this->period('r.timeawarded', 'r');
            $summary['accreditations'] = $DB->count_records_sql(
                "SELECT COUNT(1) FROM {local_pspvaccredit_record} r WHERE 1 = 1 $period",
                $params,
            );
        }
        return $summary;
    }

    /**
     * Enrolments, completions, sales and revenue of each course that had any in the period.
     *
     * @return array[] By course id: id, fullname, category, enrolments, completions, sold and revenue (float),
     *                 with the most enrolments first.
     */
    public function by_course(): array {
        global $DB;

        $rows = [];
        $add = function (array $records, string $key) use (&$rows): void {
            foreach ($records as $record) {
                $rows[$record->courseid] ??= ['enrolments' => 0, 'completions' => 0, 'sold' => 0, 'revenue' => 0.0];
                $rows[$record->courseid][$key] = $key === 'revenue' ? (float) $record->total : (int) $record->total;
            }
        };

        [$period, $params] = $this->period('ue.timecreated', 'e');
        $add($DB->get_records_sql(
            "SELECT e.courseid, COUNT(1) AS total
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
               JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
              WHERE e.courseid <> :siteid $period
           GROUP BY e.courseid",
            $params + ['siteid' => SITEID],
        ), 'enrolments');

        [$period, $params] = $this->period('cc.timecompleted', 'c');
        $add($DB->get_records_sql(
            "SELECT cc.course AS courseid, COUNT(1) AS total
               FROM {course_completions} cc
               JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
              WHERE cc.timecompleted > 0 $period
           GROUP BY cc.course",
            $params,
        ), 'completions');

        if (self::has_sales()) {
            [$period, $params] = $this->period('o.timepaid', 'o');
            $sales = $DB->get_records_sql(
                "SELECT i.courseid, COUNT(1) AS sold, COALESCE(SUM(i.total), 0) AS revenue
                   FROM {local_pspvshop_orderitem} i
                   JOIN {local_pspvshop_order} o ON o.id = i.orderid
                  WHERE o.status = 'paid' $period
               GROUP BY i.courseid",
                $params,
            );
            foreach ($sales as $sale) {
                $rows[$sale->courseid] ??= ['enrolments' => 0, 'completions' => 0, 'sold' => 0, 'revenue' => 0.0];
                $rows[$sale->courseid]['sold'] = (int) $sale->sold;
                $rows[$sale->courseid]['revenue'] = (float) $sale->revenue;
            }
        }
        if (!$rows) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(array_keys($rows));
        $courses = $DB->get_records_sql(
            "SELECT c.id, c.fullname, cat.name AS category
               FROM {course} c
          LEFT JOIN {course_categories} cat ON cat.id = c.category
              WHERE c.id $insql",
            $params,
        );
        $result = [];
        foreach ($rows as $courseid => $row) {
            if (!isset($courses[$courseid])) {
                // A course that was deleted after it was sold: its sales still count in the totals.
                continue;
            }
            $result[$courseid] = [
                'id' => (int) $courseid,
                'fullname' => $courses[$courseid]->fullname,
                'category' => (string) $courses[$courseid]->category,
            ] + $row;
        }
        uasort(
            $result,
            fn($a, $b) => [$b['enrolments'], $b['revenue'], $a['fullname']] <=> [$a['enrolments'], $a['revenue'], $b['fullname']],
        );
        return $result;
    }

    /**
     * Internal training broken down by a column of the staff list.
     *
     * Counts the enrolments and completions, in the courses of the internal category, of the users
     * whose email address is on the staff list.
     *
     * @param string $dimension One of DIMENSIONS.
     * @return array[] name, people, enrolments and completions, with the most enrolments first.
     */
    public function internal_by(string $dimension): array {
        global $DB;

        if (!in_array($dimension, self::DIMENSIONS, true) || !self::has_staff_list()) {
            return [];
        }
        $category = $DB->get_record('course_categories', ['idnumber' => self::INTERNAL_CATEGORY], 'id, path');
        if (!$category) {
            return [];
        }
        $courses = "SELECT c.id
                      FROM {course} c
                      JOIN {course_categories} cat ON cat.id = c.category
                     WHERE cat.id = :catid OR " . $DB->sql_like('cat.path', ':catpath');
        $courseparams = ['catid' => $category->id, 'catpath' => $DB->sql_like_escape($category->path) . '/%'];

        [$period, $params] = $this->period('ue.timecreated', 'e');
        $enrolments = $DB->get_records_sql(
            "SELECT s.$dimension AS name, COUNT(1) AS enrolments, COUNT(DISTINCT ue.userid) AS people
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
               JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
               JOIN {local_pspvaccess_employee} s ON s.email = LOWER(u.email)
              WHERE e.courseid IN ($courses) $period
           GROUP BY s.$dimension",
            $params + $courseparams,
        );
        [$period, $params] = $this->period('cc.timecompleted', 'c');
        $completions = $DB->get_records_sql(
            "SELECT s.$dimension AS name, COUNT(1) AS completions
               FROM {course_completions} cc
               JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
               JOIN {local_pspvaccess_employee} s ON s.email = LOWER(u.email)
              WHERE cc.timecompleted > 0 AND cc.course IN ($courses) $period
           GROUP BY s.$dimension",
            $params + $courseparams,
        );

        $rows = [];
        foreach ($enrolments as $row) {
            $name = trim((string) $row->name);
            $rows[$name] = [
                'name' => $name, 'people' => (int) $row->people, 'enrolments' => (int) $row->enrolments, 'completions' => 0,
            ];
        }
        foreach ($completions as $row) {
            $name = trim((string) $row->name);
            $rows[$name] ??= ['name' => $name, 'people' => 0, 'enrolments' => 0, 'completions' => 0];
            $rows[$name]['completions'] = (int) $row->completions;
        }
        // Most enrolments first; among equals, by name, with whoever has no value last.
        usort(
            $rows,
            fn($a, $b) => [$b['enrolments'], $a['name'] === '', $a['name']] <=> [$a['enrolments'], $b['name'] === '', $b['name']],
        );
        return $rows;
    }

    /**
     * Formats an amount of money.
     *
     * @param float $amount
     * @param string $currency ISO code.
     * @return string
     */
    public static function money(float $amount, string $currency): string {
        if (class_exists(\local_pspvshop\money::class)) {
            return \local_pspvshop\money::format((int) round($amount * 100), $currency);
        }
        return format_float($amount, 2) . ' ' . $currency;
    }
}
