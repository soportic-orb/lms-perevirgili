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
 * Settings and administration pages of local_pspvmailing.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/pspvmailing:manage', \core\context\system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_pspvmailing', get_string('pluginname', 'local_pspvmailing')));

    $ADMIN->add('local_pspvmailing', new admin_externalpage(
        'local_pspvmailing_campaigns',
        get_string('campaigns', 'local_pspvmailing'),
        new \core\url('/local/pspvmailing/manage/index.php'),
        'local/pspvmailing:manage',
    ));

    $settings = new admin_settingpage('local_pspvmailing_settings', get_string('settings', 'local_pspvmailing'));
    if ($ADMIN->fulltree) {
        // Which document gives consent to commercial communications.
        $policies = ['' => get_string('setting_commercialpolicy_default', 'local_pspvmailing')];
        if (class_exists(\tool_policy\api::class)) {
            foreach (\tool_policy\api::list_policies() as $policy) {
                $version = $policy->currentversion ?? (reset($policy->draftversions) ?: null);
                if ($version) {
                    $policies[$policy->id] = format_string($version->name);
                }
            }
        }
        $settings->add(new admin_setting_configselect(
            'local_pspvmailing/commercialpolicy',
            get_string('setting_commercialpolicy', 'local_pspvmailing'),
            get_string('setting_commercialpolicy_desc', 'local_pspvmailing'),
            '',
            $policies,
        ));
        // The hospital decided that workers also receive commercial campaigns without that consent.
        // Its data protection officer still has to validate it; unticking this asks workers for consent too.
        $settings->add(new admin_setting_configcheckbox(
            'local_pspvmailing/workersalways',
            get_string('setting_workersalways', 'local_pspvmailing'),
            get_string('setting_workersalways_desc', 'local_pspvmailing'),
            1,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvmailing/workercohort',
            get_string('setting_workercohort', 'local_pspvmailing'),
            get_string('setting_workercohort_desc', 'local_pspvmailing'),
            'pspv-workers',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_confightmleditor(
            'local_pspvmailing/footer',
            get_string('setting_footer', 'local_pspvmailing'),
            get_string('setting_footer_desc', 'local_pspvmailing'),
            '',
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvmailing/batchsize',
            get_string('setting_batchsize', 'local_pspvmailing'),
            get_string('setting_batchsize_desc', 'local_pspvmailing'),
            100,
            PARAM_INT,
        ));
    }
    $ADMIN->add('local_pspvmailing', $settings);
}
