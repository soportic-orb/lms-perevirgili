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
 * Records who attended a session.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvaccredit\accreditation;
use local_pspvaccredit\attendance;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$session = attendance::session($id);
$course = get_course($session->courseid);
require_login($course);
$context = \core\context\course::instance($course->id);
require_capability('local/pspvaccredit:markattendance', $context);

$return = new \core\url('/local/pspvaccredit/index.php', ['id' => $course->id]);
$PAGE->set_url(new \core\url('/local/pspvaccredit/attendance.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('attendance', 'local_pspvaccredit'));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->navbar->add(get_string('accreditation', 'local_pspvaccredit'), $return);

$students = accreditation::students((int) $course->id);

if (optional_param('save', 0, PARAM_BOOL) && data_submitted()) {
    require_sesskey();
    $present = optional_param_array('present', [], PARAM_INT);
    attendance::mark($id, array_keys($students), array_keys(array_filter($present)), (int) $USER->id);
    // Attendance may be what somebody was missing to pass.
    accreditation::sync_course((int) $course->id);
    redirect($return, get_string('attendance_saved', 'local_pspvaccredit'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$present = array_flip(attendance::present($id));
$rows = [];
foreach ($students as $student) {
    $rows[] = ['id' => $student->id, 'name' => fullname($student), 'present' => isset($present[$student->id])];
}

echo $OUTPUT->header();
$sessionname = format_string($session->name, true, ['context' => $context]);
echo $OUTPUT->heading(get_string('attendance_of', 'local_pspvaccredit', $sessionname));
echo $OUTPUT->render_from_template('local_pspvaccredit/attendance', [
    'action' => $PAGE->url->out(false),
    'sesskey' => sesskey(),
    'date' => userdate($session->sessiondate, get_string('strftimedatetimeshort', 'core_langconfig')),
    'students' => $rows,
    'hasstudents' => !empty($rows),
    'returnurl' => $return->out(false),
]);
echo $OUTPUT->footer();
