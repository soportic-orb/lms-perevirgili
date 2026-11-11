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
 * Employee list: import from CSV and re-synchronise the workers cohort.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvaccess\employee_list;
use local_pspvaccess\form\import_employees;
use local_pspvaccess\worker_manager;
use local_pspvaccess\worker_policy;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_pspvaccess_employees');
require_capability('local/pspvaccess:manageemployees', \core\context\system::instance());

$pageurl = new \core\url('/local/pspvaccess/employees.php');

if (optional_param('resync', 0, PARAM_BOOL)) {
    require_sesskey();
    $counts = worker_manager::resync_all(worker_manager::ORIGIN_MANUAL, $USER->id);
    redirect($pageurl, get_string('resync_done', 'local_pspvaccess', $counts), null, \core\output\notification::NOTIFY_SUCCESS);
}

$form = new import_employees();
if ($data = $form->get_data()) {
    try {
        $content = $form->get_file_content('csvfile');
        $counts = employee_list::import_csv($content, $data->delimiter, $data->encoding, !empty($data->replace));
    } catch (\moodle_exception $e) {
        redirect($pageurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
    $sync = worker_manager::resync_all(worker_manager::ORIGIN_MANUAL, $USER->id);
    $counts['added'] = $sync['added'];
    $counts['removed'] = $sync['removed'];
    $message = get_string('employees_imported', 'local_pspvaccess', $counts);
    redirect($pageurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

$imported = (int) get_config('local_pspvaccess', 'employeesimported');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('employees_title', 'local_pspvaccess'));
echo $OUTPUT->render_from_template('local_pspvaccess/employees_status', [
    'count' => employee_list::count(),
    'imported' => $imported ? userdate($imported, get_string('strftimedatetimeshort', 'core_langconfig')) : get_string('never'),
    'listenabled' => in_array(worker_policy::RULE_LIST, worker_policy::enabled_rules(), true),
    'norules' => !worker_policy::enabled_rules(),
    'settingsurl' => (new \core\url('/admin/settings.php', ['section' => 'local_pspvaccess_settings']))->out(false),
    'resyncurl' => (new \core\url($pageurl, ['resync' => 1, 'sesskey' => sesskey()]))->out(false),
]);
$form->display();
echo $OUTPUT->footer();
