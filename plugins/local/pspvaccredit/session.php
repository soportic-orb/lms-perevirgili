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
 * Creates, changes or deletes a session of a course.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvaccredit\attendance;

require(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$session = $id ? attendance::session($id) : null;
$courseid = $session ? (int) $session->courseid : required_param('course', PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = \core\context\course::instance($course->id);
require_capability('local/pspvaccredit:manage', $context);

$return = new \core\url('/local/pspvaccredit/index.php', ['id' => $course->id]);
$PAGE->set_url(new \core\url('/local/pspvaccredit/session.php', $id ? ['id' => $id] : ['course' => $course->id]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('session', 'local_pspvaccredit'));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->navbar->add(get_string('accreditation', 'local_pspvaccredit'), $return);

if ($session && $delete) {
    if (optional_param('confirm', 0, PARAM_BOOL)) {
        require_sesskey();
        attendance::delete_session($id);
        redirect($return, get_string('session_deleted', 'local_pspvaccredit'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('session_delete_confirm', 'local_pspvaccredit', format_string($session->name)),
        new \core\url($PAGE->url, ['delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $return,
    );
    echo $OUTPUT->footer();
    exit;
}

$form = new \local_pspvaccredit\form\session_form($PAGE->url);
$form->set_data($session ? (array) $session + ['course' => $course->id] : ['course' => $course->id, 'sessiondate' => time()]);

if ($form->is_cancelled()) {
    redirect($return);
}
if ($data = $form->get_data()) {
    $data->courseid = $course->id;
    $data->id = $id;
    attendance::save_session($data);
    redirect($return, get_string('session_saved', 'local_pspvaccredit'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($session ? 'session_edit' : 'session_add', 'local_pspvaccredit'));
$form->display();
if ($session) {
    echo $OUTPUT->single_button(
        new \core\url($PAGE->url, ['delete' => 1]),
        get_string('session_delete', 'local_pspvaccredit'),
        'get',
    );
}
echo $OUTPUT->footer();
