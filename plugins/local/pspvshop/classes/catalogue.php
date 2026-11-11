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

namespace local_pspvshop;

/**
 * The public catalogue: the products on sale, filtered and ready to be shown as cards.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue {
    /** @var string[] Price ranges offered as a filter => [minimum, maximum] in currency units (null: no bound). */
    public const PRICE_RANGES = [
        'upto50' => [null, 50],
        '50to150' => [50, 150],
        'over150' => [150, null],
    ];

    /** @var int Number of cover colours the stylesheets define. */
    protected const TONES = 6;

    /** @var string Shortname of the course custom field with the accreditation credits. */
    public const CREDITS_FIELD = 'pspv_credits';

    /**
     * The category whose courses can be sold.
     *
     * @return \core_course_category|null Null when it is not set, does not exist or the user cannot see it.
     */
    public static function category(): ?\core_course_category {
        global $DB;

        $idnumber = get_config('local_pspvshop', 'category');
        $idnumber = $idnumber === false ? 'pspv-external' : trim((string) $idnumber);
        $id = $idnumber === '' ? 0 : $DB->get_field('course_categories', 'id', ['idnumber' => $idnumber]);
        return $id ? \core_course_category::get($id, IGNORE_MISSING) : null;
    }

    /**
     * The areas a buyer can filter by: the categories that hold products on sale.
     *
     * @return string[] Category id => name.
     */
    public static function areas(): array {
        $areas = [];
        foreach (self::search([], 0, 0)['cards'] as $card) {
            $areas[$card['categoryid']] = $card['category'];
        }
        \core_collator::asort($areas);
        return $areas;
    }

    /**
     * Searches the products on sale.
     *
     * @param array $filters Optional keys: q (text), area (category id), mode, price (key of PRICE_RANGES),
     *                       start ("upcoming" or "open"), credits (true for courses with accreditation credits).
     * @param int $page Page number, from 0.
     * @param int $perpage Cards per page; 0 for all.
     * @return array cards (see card()) and total (int).
     */
    public static function search(array $filters = [], int $page = 0, int $perpage = 12): array {
        global $DB, $USER;

        $now = time();
        $where = [
            'p.enabled = 1',
            'c.visible = 1',
            '(p.salestart = 0 OR p.salestart <= :now1)',
            '(p.saleend = 0 OR p.saleend >= :now2)',
        ];
        $params = ['now1' => $now, 'now2' => $now, 'now3' => $now];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $where[] = '(' . $DB->sql_like('c.fullname', ':q1', false, false) . ' OR '
                . $DB->sql_like('c.summary', ':q2', false, false) . ')';
            $params['q1'] = $params['q2'] = '%' . $DB->sql_like_escape($query) . '%';
        }
        if (!empty($filters['area'])) {
            $where[] = 'c.category = :area';
            $params['area'] = (int) $filters['area'];
        }
        if (in_array($filters['mode'] ?? '', product::MODES, true)) {
            $where[] = 'p.mode = :mode';
            $params['mode'] = $filters['mode'];
        }
        if (isset(self::PRICE_RANGES[$filters['price'] ?? ''])) {
            [$minimum, $maximum] = self::PRICE_RANGES[$filters['price']];
            if ($minimum !== null) {
                $where[] = 'p.price >= :pricemin';
                $params['pricemin'] = $minimum;
            }
            if ($maximum !== null) {
                $where[] = 'p.price <= :pricemax';
                $params['pricemax'] = $maximum;
            }
        }
        if (($filters['start'] ?? '') === 'upcoming') {
            $where[] = 'c.startdate > :startnow';
            $params['startnow'] = $now;
        } else if (($filters['start'] ?? '') === 'open') {
            $where[] = 'c.startdate <= :startnow';
            $params['startnow'] = $now;
        }

        // Accreditation credits, when the course custom field that holds them exists.
        $credits = 'NULL AS credits';
        $creditsjoin = '';
        if ($fieldid = self::credits_field()) {
            $credits = 'cr.decvalue AS credits';
            $creditsjoin = 'LEFT JOIN {customfield_data} cr ON cr.instanceid = c.id AND cr.fieldid = :creditsfield';
            $params['creditsfield'] = $fieldid;
            if (!empty($filters['credits'])) {
                $where[] = 'cr.decvalue > 0';
            }
        }

        // Courses that have not started yet come first, soonest first.
        $rows = $DB->get_records_sql(
            "SELECT p.*, c.fullname, c.summary, c.summaryformat, c.category, c.startdate, $credits
               FROM {local_pspvshop_product} p
               JOIN {course} c ON c.id = p.courseid
                    $creditsjoin
              WHERE " . implode(' AND ', $where) . "
           ORDER BY CASE WHEN c.startdate > :now3 THEN 0 ELSE 1 END, c.startdate, c.fullname, p.id",
            $params,
        );

        // Only what the current user is allowed to see in the course listings.
        $categories = [];
        $visible = [];
        foreach ($rows as $row) {
            if (!array_key_exists($row->category, $categories)) {
                $categories[$row->category] = \core_course_category::get($row->category, IGNORE_MISSING);
            }
            if ($categories[$row->category]) {
                $visible[] = $row;
            }
        }

        $total = count($visible);
        if ($perpage > 0) {
            $visible = array_slice($visible, $page * $perpage, $perpage);
        }
        $userid = isloggedin() && !isguestuser() ? (int) $USER->id : null;
        $cards = [];
        foreach ($visible as $row) {
            $cards[] = self::card($row, $categories[$row->category], $userid);
        }
        return ['cards' => $cards, 'total' => $total];
    }

    /**
     * Id of the course custom field with the accreditation credits (the one local_pspvaccredit creates).
     *
     * @return int 0 when there is no such field.
     */
    public static function credits_field(): int {
        global $DB;

        $sql = "SELECT f.id
                  FROM {customfield_field} f
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE c.component = :component AND c.area = :area AND f.shortname = :shortname AND f.type = :type";
        $params = ['component' => 'core_course', 'area' => 'course', 'shortname' => self::CREDITS_FIELD, 'type' => 'number'];
        return (int) $DB->get_field_sql($sql, $params, IGNORE_MULTIPLE);
    }

    /**
     * A product as the templates show it.
     *
     * @param \stdClass $row Product with the fullname, summary, summaryformat, category and startdate of its course,
     *                       and optionally its accreditation credits.
     * @param \core_course_category $category
     * @param int|null $userid The current user, when they have an account.
     * @return array
     */
    public static function card(\stdClass $row, \core_course_category $category, ?int $userid): array {
        $context = \core\context\course::instance($row->courseid);
        $summary = html_entity_decode(
            strip_tags(format_text($row->summary, $row->summaryformat, ['context' => $context])),
            ENT_QUOTES | ENT_HTML5,
        );
        $general = money::cents($row->price);
        $mine = product::price_for($row, $userid);
        [, , $topay] = money::split($mine, product::tax_rate($row));
        [, , $generaltopay] = money::split($general, product::tax_rate($row));
        $seats = product::seats_left($row);
        $image = self::image((int) $row->courseid);
        $rowcredits = (float) ($row->credits ?? 0);

        return [
            'id' => (int) $row->id,
            'courseid' => (int) $row->courseid,
            'url' => product::url($row->id)->out(false),
            'name' => format_string($row->fullname, true, ['context' => $context]),
            'categoryid' => (int) $category->id,
            'category' => $category->get_formatted_name(),
            'summary' => shorten_text(trim(preg_replace('/\s+/', ' ', $summary)), 140),
            'hasimage' => $image !== null,
            'image' => (string) $image,
            'tone' => $row->courseid % self::TONES + 1,
            'hasmode' => !empty($row->mode),
            'mode' => empty($row->mode) ? '' : get_string('mode_' . $row->mode, 'local_pspvshop'),
            'hasstart' => $row->startdate > time(),
            'start' => userdate($row->startdate, get_string('strftimedate', 'core_langconfig')),
            'hascredits' => $rowcredits > 0,
            'credits' => get_string('credits_n', 'local_pspvshop', format_float($rowcredits, 1, true, true)),
            'price' => money::format($topay),
            'isfree' => $topay === 0,
            'hasreduction' => $mine < $general,
            'generalprice' => money::format($generaltopay),
            'haslimit' => $seats !== null,
            'seats' => (int) $seats,
            'full' => $seats === 0,
        ];
    }

    /**
     * URL of the first image among the overview files of a course.
     *
     * @param int $courseid
     * @return string|null
     */
    public static function image(int $courseid): ?string {
        $files = get_file_storage()->get_area_files(
            \core\context\course::instance($courseid)->id,
            'course',
            'overviewfiles',
            0,
            'sortorder, id',
            false,
        );
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return \core\url::make_pluginfile_url(
                    $file->get_contextid(),
                    'course',
                    'overviewfiles',
                    null,
                    $file->get_filepath(),
                    $file->get_filename(),
                )->out(false);
            }
        }
        return null;
    }
}
