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
 * Audit log of the automatic changes of the workers cohort.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_pspvaccess_audit');
require_capability('local/pspvaccess:viewaudit', \core\context\system::instance());

$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;
$pageurl = new \core\url('/local/pspvaccess/audit.php');

$total = $DB->count_records('local_pspvaccess_audit');
$userfields = \core_user\fields::for_name()->get_sql('u', false, 'user', '', false)->selects;
$rows = $DB->get_records_sql(
    "SELECT a.*, $userfields, u.email AS useremail
       FROM {local_pspvaccess_audit} a
  LEFT JOIN {user} u ON u.id = a.userid
   ORDER BY a.timecreated DESC, a.id DESC",
    [],
    $page * $perpage,
    $perpage,
);

$entries = [];
foreach ($rows as $row) {
    $user = \core_user\fields::for_name()->get_required_fields();
    $name = (object) [];
    foreach ($user as $field) {
        $name->$field = $row->{'user' . $field} ?? '';
    }
    $rules = [];
    foreach (array_filter(explode(',', $row->reason)) as $rule) {
        $rules[] = get_string('rule_' . $rule, 'local_pspvaccess');
    }
    $entries[] = [
        'time' => userdate($row->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
        'fullname' => $row->useremail !== null ? fullname($name) : get_string('deleteduser', 'local_pspvaccess'),
        'email' => (string) $row->useremail,
        'action' => get_string('audit_action_' . $row->action, 'local_pspvaccess'),
        'added' => $row->action === 'added',
        'reason' => implode(', ', $rules),
        'origin' => get_string('audit_origin_' . $row->origin, 'local_pspvaccess'),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('audit_title', 'local_pspvaccess'));
echo $OUTPUT->render_from_template('local_pspvaccess/audit', ['entries' => $entries, 'hasentries' => !empty($entries)]);
echo $OUTPUT->paging_bar($total, $page, $perpage, $pageurl);
echo $OUTPUT->footer();
