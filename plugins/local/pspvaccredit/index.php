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
 * Accreditation of a course: its data, sessions, who passed and the diploma.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvaccredit\accreditation;
use local_pspvaccredit\attendance;
use local_pspvaccredit\certificate;
use local_pspvaccredit\evaluator;

require(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);
$context = \core\context\course::instance($course->id);
$canmanage = has_capability('local/pspvaccredit:manage', $context);
$canmark = has_capability('local/pspvaccredit:markattendance', $context);
$canview = has_capability('local/pspvaccredit:viewreport', $context);
if (!$canview && !$canmark) {
    require_capability('local/pspvaccredit:viewreport', $context);
}

$url = new \core\url('/local/pspvaccredit/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('accreditation', 'local_pspvaccredit'));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

$evaluator = new evaluator($course);
$settings = $evaluator->settings();

if ($action !== '') {
    require_sesskey();
    require_capability('local/pspvaccredit:manage', $context);
    $message = '';
    switch ($action) {
        case 'sync':
            $message = get_string('sync_done', 'local_pspvaccredit', accreditation::sync_course((int) $course->id));
            break;
        case 'award':
            $userid = required_param('user', PARAM_INT);
            if ($settings->accredited && is_enrolled($context, $userid)) {
                accreditation::award($course, $userid, $settings, $evaluator->evaluate($userid), (int) $USER->id);
                $message = get_string('award_done', 'local_pspvaccredit');
            }
            break;
        case 'revoke':
            $record = $DB->get_record('local_pspvaccredit_record', [
                'id' => required_param('record', PARAM_INT), 'courseid' => $course->id,
            ], '*', MUST_EXIST);
            accreditation::revoke((int) $record->id);
            $message = get_string('revoke_done', 'local_pspvaccredit');
            break;
        case 'adddiploma':
            if (certificate::available()) {
                $cm = certificate::add_to_course($course);
                redirect(new \core\url('/mod/customcert/view.php', ['id' => $cm->id]));
            }
            break;
    }
    redirect($url, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($download !== '') {
    require_capability('local/pspvaccredit:viewreport', $context);
    $records = accreditation::records((int) $course->id);
    \core\dataformat::download_data(
        clean_filename('accreditation-' . $course->shortname),
        $download,
        accreditation::export_columns(false),
        new ArrayIterator(array_values($records)),
        fn($record) => accreditation::export_row($record, false),
    );
    exit;
}

$number = fn(?float $value, int $decimals = 1) => $value === null ? '' : format_float($value, $decimals, true, true);
$percent = fn(?float $value) => $value === null ? '–' : format_float($value, 0) . ' %';
$dateformat = get_string('strftimedatefullshort', 'core_langconfig');

// Sessions.
$sessions = [];
foreach (attendance::sessions((int) $course->id) as $session) {
    $sessions[] = [
        'name' => format_string($session->name, true, ['context' => $context]),
        'date' => userdate($session->sessiondate, get_string('strftimedatetimeshort', 'core_langconfig')),
        'duration' => $session->duration ? get_string('numminutes', 'moodle', $session->duration) : '',
        'present' => count(attendance::present((int) $session->id)),
        'canmark' => $canmark,
        'markurl' => (new \core\url('/local/pspvaccredit/attendance.php', ['id' => $session->id]))->out(false),
        'canmanage' => $canmanage,
        'editurl' => (new \core\url('/local/pspvaccredit/session.php', ['id' => $session->id]))->out(false),
    ];
}

// Students.
$students = [];
$passed = 0;
if ($canview) {
    $records = [];
    foreach ($DB->get_records('local_pspvaccredit_record', ['courseid' => $course->id]) as $record) {
        $records[$record->userid] = $record;
    }
    foreach (accreditation::students((int) $course->id) as $student) {
        $evaluation = $evaluator->evaluate((int) $student->id);
        $record = $records[$student->id] ?? null;
        $passed += $record ? 1 : 0;
        $pending = array_map(fn($reason) => get_string('pending_' . $reason, 'local_pspvaccredit'), $evaluation->pending);
        $students[] = [
            'name' => fullname($student),
            'attendance' => $percent($evaluation->attendance),
            'grade' => $percent($evaluation->grade),
            'hascompletion' => $evaluation->completed !== null,
            'completed' => (bool) $evaluation->completed,
            'passed' => (bool) $record,
            'date' => $record ? userdate($record->timeawarded, $dateformat) : '',
            'manual' => $record && $record->awardedby,
            'pending' => implode(', ', $pending),
            'canmanage' => $canmanage && $settings->accredited,
            'awardurl' => (new \core\url($url, ['action' => 'award', 'user' => $student->id, 'sesskey' => sesskey()]))->out(false),
            'revokeurl' => $record
                ? (new \core\url($url, ['action' => 'revoke', 'record' => $record->id, 'sesskey' => sesskey()]))->out(false)
                : '',
        ];
    }
}

$diploma = certificate::for_course((int) $course->id);
$data = [
    'accredited' => $settings->accredited,
    'nocriteria' => $settings->accredited && !$evaluator->has_criteria(),
    'canedit' => has_capability('moodle/course:update', $context),
    'editurl' => (new \core\url('/course/edit.php', ['id' => $course->id]))->out(false),
    'filecode' => $settings->filecode,
    'credits' => $number($settings->credits),
    'hours' => $number($settings->hours),
    'period' => $settings->accreditedfrom || $settings->accrediteduntil
        ? trim(($settings->accreditedfrom ? userdate($settings->accreditedfrom, $dateformat) : '…') . ' – '
            . ($settings->accrediteduntil ? userdate($settings->accrediteduntil, $dateformat) : '…'))
        : '',
    'minattendance' => $settings->minattendance === null ? '' : $percent($settings->minattendance),
    'mingrade' => $settings->mingrade === null ? '' : $percent($settings->mingrade),
    'showsessions' => $canmark || $canmanage,
    'sessions' => $sessions,
    'hassessions' => !empty($sessions),
    'canmanage' => $canmanage,
    'addsessionurl' => (new \core\url('/local/pspvaccredit/session.php', ['course' => $course->id]))->out(false),
    'canview' => $canview,
    'students' => $students,
    'hasstudents' => !empty($students),
    'passedcount' => $passed,
    'studentcount' => count($students),
    'syncurl' => (new \core\url($url, ['action' => 'sync', 'sesskey' => sesskey()]))->out(false),
    'download' => $canview && $passed
        ? $OUTPUT->download_dataformat_selector(
            get_string('export', 'local_pspvaccredit'),
            $url->out_omit_querystring(),
            'download',
            ['id' => $course->id],
        )
        : '',
    'diplomaavailable' => certificate::available(),
    'hasdiploma' => (bool) $diploma,
    'diplomaurl' => $diploma ? (new \core\url('/mod/customcert/view.php', ['id' => $diploma->id]))->out(false) : '',
    'adddiplomaurl' => (new \core\url($url, ['action' => 'adddiploma', 'sesskey' => sesskey()]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('accreditation', 'local_pspvaccredit'));
echo $OUTPUT->render_from_template('local_pspvaccredit/course', $data);
echo $OUTPUT->footer();
