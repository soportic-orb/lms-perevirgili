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
 * Register of who passed an accredited course, across the whole site.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvaccredit\accreditation;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$download = optional_param('download', '', PARAM_ALPHA);
$from = optional_param('from', '', PARAM_RAW_TRIMMED);
$to = optional_param('to', '', PARAM_RAW_TRIMMED);

admin_externalpage_setup('local_pspvaccredit_records');
require_capability('local/pspvaccredit:viewreport', \core\context\system::instance());

// Dates as typed in the filter (YYYY-MM-DD), in the time zone of the user.
$parse = function (string $date, bool $endofday): int {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
        return 0;
    }
    [$hour, $minute, $second] = $endofday ? [23, 59, 59] : [0, 0, 0];
    return make_timestamp((int) $parts[1], (int) $parts[2], (int) $parts[3], $hour, $minute, $second);
};
$records = accreditation::records(0, $parse($from, false), $parse($to, true));

if ($download !== '') {
    \core\dataformat::download_data(
        'accreditation-' . userdate(time(), '%Y%m%d'),
        $download,
        accreditation::export_columns(true),
        new ArrayIterator(array_values($records)),
        fn($record) => accreditation::export_row($record, true),
    );
    exit;
}

$rows = [];
foreach ($records as $record) {
    $rows[] = accreditation::export_row($record, true) + [
        'name' => fullname($record),
        'courseurl' => (new \core\url('/local/pspvaccredit/index.php', ['id' => $record->courseid]))->out(false),
    ];
}
$filter = array_filter(['from' => $parse($from, false) ? $from : '', 'to' => $parse($to, true) ? $to : '']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('records', 'local_pspvaccredit'));
echo $OUTPUT->render_from_template('local_pspvaccredit/records', [
    'action' => $PAGE->url->out(false),
    'from' => $filter['from'] ?? '',
    'to' => $filter['to'] ?? '',
    'records' => $rows,
    'hasrecords' => !empty($rows),
    'count' => count($rows),
    'download' => $rows
        ? $OUTPUT->download_dataformat_selector(
            get_string('export', 'local_pspvaccredit'),
            $PAGE->url->out(false),
            'download',
            $filter,
        )
        : '',
]);
echo $OUTPUT->footer();
