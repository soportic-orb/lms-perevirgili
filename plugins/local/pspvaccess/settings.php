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
 * Settings and administration pages of local_pspvaccess.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$pspvaccesscaps = ['local/pspvaccess:manageemployees', 'local/pspvaccess:viewaudit'];
if ($hassiteconfig || has_any_capability($pspvaccesscaps, \core\context\system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_pspvaccess', get_string('pluginname', 'local_pspvaccess')));

    $settings = new admin_settingpage('local_pspvaccess_settings', get_string('settings_title', 'local_pspvaccess'));
    if ($ADMIN->fulltree) {
        // Who is a worker is the hospital's decision: every rule is off until it is configured here.
        $settings->add(new admin_setting_heading(
            'local_pspvaccess/intro',
            '',
            get_string('settings_intro', 'local_pspvaccess'),
        ));

        $issuers = [0 => get_string('none')];
        foreach (\core\oauth2\api::get_all_issuers(true) as $issuer) {
            $issuers[$issuer->get('id')] = $issuer->get('name');
        }
        $settings->add(new admin_setting_configselect(
            'local_pspvaccess/workerissuer',
            get_string('settings_workerissuer', 'local_pspvaccess'),
            get_string('settings_workerissuer_desc', 'local_pspvaccess'),
            0,
            $issuers,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvaccess/tenantmaxage',
            get_string('settings_tenantmaxage', 'local_pspvaccess'),
            get_string('settings_tenantmaxage_desc', 'local_pspvaccess'),
            0,
            PARAM_INT,
        ));
        $settings->add(new admin_setting_configtextarea(
            'local_pspvaccess/emaildomains',
            get_string('settings_emaildomains', 'local_pspvaccess'),
            get_string('settings_emaildomains_desc', 'local_pspvaccess'),
            '',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_pspvaccess/useemployeelist',
            get_string('settings_useemployeelist', 'local_pspvaccess'),
            get_string('settings_useemployeelist_desc', 'local_pspvaccess'),
            0,
        ));
        $settings->add(new admin_setting_configselect(
            'local_pspvaccess/rulemode',
            get_string('settings_rulemode', 'local_pspvaccess'),
            get_string('settings_rulemode_desc', 'local_pspvaccess'),
            'any',
            [
                'any' => get_string('settings_rulemode_any', 'local_pspvaccess'),
                'all' => get_string('settings_rulemode_all', 'local_pspvaccess'),
            ],
        ));
    }
    $ADMIN->add('local_pspvaccess', $settings);

    $ADMIN->add('local_pspvaccess', new admin_externalpage(
        'local_pspvaccess_employees',
        get_string('employees_title', 'local_pspvaccess'),
        new \core\url('/local/pspvaccess/employees.php'),
        'local/pspvaccess:manageemployees',
    ));
    $ADMIN->add('local_pspvaccess', new admin_externalpage(
        'local_pspvaccess_audit',
        get_string('audit_title', 'local_pspvaccess'),
        new \core\url('/local/pspvaccess/audit.php'),
        'local/pspvaccess:viewaudit',
    ));
}
