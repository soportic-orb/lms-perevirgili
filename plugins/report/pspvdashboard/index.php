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

/**
 * Dashboard of the campus: enrolments, completions, sales and internal training.
 *
 * @package    report_pspvdashboard
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_pspvdashboard\stats;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$download = optional_param('download', '', PARAM_ALPHA);
$table = optional_param('table', 'courses', PARAM_ALPHA);
$from = optional_param('from', '', PARAM_RAW_TRIMMED);
$to = optional_param('to', '', PARAM_RAW_TRIMMED);

admin_externalpage_setup('report_pspvdashboard', '', null, '', ['pagelayout' => 'report']);
require_capability('report/pspvdashboard:view', \core\context\system::instance());

// Dates as typed in the filter (YYYY-MM-DD), in the time zone of the user.
$parse = function (string $date, bool $endofday): int {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
        return 0;
    }
    [$hour, $minute, $second] = $endofday ? [23, 59, 59] : [0, 0, 0];
    return make_timestamp((int) $parts[1], (int) $parts[2], (int) $parts[3], $hour, $minute, $second);
};
$fromtime = $parse($from, false);
$totime = $parse($to, true);
$filter = array_filter(['from' => $fromtime ? $from : '', 'to' => $totime ? $to : '']);

$stats = new stats($fromtime, $totime);
$summary = $stats->summary();
$hassales = stats::has_sales();
$currency = $summary['currency'] ?? '';
$courses = $stats->by_course();
$dimensions = [];
foreach (stats::DIMENSIONS as $dimension) {
    $dimensions[$dimension] = $stats->internal_by($dimension);
}
$unknown = get_string('unknown', 'report_pspvdashboard');

if ($download !== '') {
    require_sesskey();
    if ($table === 'courses') {
        $columns = [
            'fullname' => get_string('course'),
            'category' => get_string('category'),
            'enrolments' => get_string('enrolments', 'report_pspvdashboard'),
            'completions' => get_string('completions', 'report_pspvdashboard'),
        ];
        if ($hassales) {
            $columns += [
                'sold' => get_string('sold', 'report_pspvdashboard'),
                'revenue' => get_string('revenue', 'report_pspvdashboard'),
            ];
        }
        $rows = array_map(fn($row) => array_intersect_key(
            ['revenue' => format_float($row['revenue'], 2, true)] + $row,
            $columns,
        ), array_values($courses));
        $rows = array_map(fn($row) => array_merge(array_fill_keys(array_keys($columns), ''), $row), $rows);
    } else if (isset($dimensions[$table])) {
        $columns = [
            'name' => get_string('dimension_' . $table, 'report_pspvdashboard'),
            'people' => get_string('people', 'report_pspvdashboard'),
            'enrolments' => get_string('enrolments', 'report_pspvdashboard'),
            'completions' => get_string('completions', 'report_pspvdashboard'),
        ];
        $rows = array_map(fn($row) => ['name' => $row['name'] === '' ? $unknown : $row['name']] + $row, $dimensions[$table]);
    } else {
        throw new \core\exception\moodle_exception('invalidparameter', 'debug');
    }
    \core\dataformat::download_data('campus-' . $table . '-' . userdate(time(), '%Y%m%d'), $download, $columns, $rows);
    exit;
}

$selector = fn(string $name) => $OUTPUT->download_dataformat_selector(
    get_string('export', 'report_pspvdashboard'),
    $PAGE->url->out_omit_querystring(),
    'download',
    $filter + ['table' => $name, 'sesskey' => sesskey()],
);
$rate = fn(int $done, int $total) => $total > 0 ? format_float(min(100, $done * 100 / $total), 0) . ' %' : '–';

$courserows = [];
foreach ($courses as $row) {
    $courserows[] = [
        'name' => format_string($row['fullname']),
        'url' => (new \core\url('/course/view.php', ['id' => $row['id']]))->out(false),
        'category' => format_string($row['category']),
        'enrolments' => $row['enrolments'],
        'completions' => $row['completions'],
        'sold' => $row['sold'],
        'revenue' => $hassales ? stats::money($row['revenue'], $currency) : '',
    ];
}
$breakdowns = [];
foreach ($dimensions as $dimension => $rows) {
    $breakdowns[] = [
        'key' => $dimension,
        'title' => get_string('internal_by_' . $dimension, 'report_pspvdashboard'),
        'label' => get_string('dimension_' . $dimension, 'report_pspvdashboard'),
        'rows' => array_map(fn($row) => [
            'name' => $row['name'] === '' ? $unknown : $row['name'],
            'people' => $row['people'],
            'enrolments' => $row['enrolments'],
            'completions' => $row['completions'],
            'rate' => $rate($row['completions'], $row['enrolments']),
        ], $rows),
        'hasrows' => !empty($rows),
        'download' => $rows ? $selector($dimension) : '',
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_pspvdashboard'));
echo $OUTPUT->render_from_template('report_pspvdashboard/dashboard', [
    'action' => $PAGE->url->out_omit_querystring(),
    'from' => $filter['from'] ?? '',
    'to' => $filter['to'] ?? '',
    'hasfilter' => !empty($filter),
    'enrolments' => $summary['enrolments'],
    'completions' => $summary['completions'],
    'activeusers' => $summary['activeusers'],
    'hassales' => $hassales,
    'orders' => $summary['orders'] ?? 0,
    'revenue' => $hassales ? stats::money($summary['revenue'], $currency) : '',
    'hasaccreditation' => stats::has_accreditation(),
    'accreditations' => $summary['accreditations'] ?? 0,
    'courses' => $courserows,
    'hascourses' => !empty($courserows),
    'coursesdownload' => $courserows ? $selector('courses') : '',
    'hasstafflist' => stats::has_staff_list(),
    'breakdowns' => $breakdowns,
]);
echo $OUTPUT->footer();
