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
 * Settings and administration pages of local_pspvaccredit.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/pspvaccredit:viewreport', \core\context\system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_pspvaccredit', get_string('pluginname', 'local_pspvaccredit')));

    $settings = new admin_settingpage('local_pspvaccredit_settings', get_string('settings', 'local_pspvaccredit'));
    if ($ADMIN->fulltree) {
        require_once($CFG->dirroot . '/user/profile/lib.php');
        // Where the identity document of a student is kept is the hospital's decision: nothing is assumed.
        $choices = ['' => get_string('setting_idfield_none', 'local_pspvaccredit'), 'idnumber' => get_string('idnumber')];
        foreach (profile_get_custom_fields() as $field) {
            $choices[$field->shortname] = format_string($field->name);
        }
        $settings->add(new admin_setting_configselect(
            'local_pspvaccredit/idfield',
            get_string('setting_idfield', 'local_pspvaccredit'),
            get_string('setting_idfield_desc', 'local_pspvaccredit'),
            '',
            $choices,
        ));
    }
    $ADMIN->add('local_pspvaccredit', $settings);

    $ADMIN->add('local_pspvaccredit', new admin_externalpage(
        'local_pspvaccredit_records',
        get_string('records', 'local_pspvaccredit'),
        new \core\url('/local/pspvaccredit/records.php'),
        'local/pspvaccredit:viewreport',
    ));
}
