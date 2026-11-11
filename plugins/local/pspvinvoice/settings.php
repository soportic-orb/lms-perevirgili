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
 * Settings and administration pages of the invoicing plugin.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/pspvinvoice:manage', \core\context\system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_pspvinvoice', get_string('pluginname', 'local_pspvinvoice')));

    $settings = new admin_settingpage('local_pspvinvoice_settings', get_string('settings', 'local_pspvinvoice'));
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox(
            'local_pspvinvoice/enabled',
            get_string('setting_enabled', 'local_pspvinvoice'),
            get_string('setting_enabled_desc', 'local_pspvinvoice'),
            1,
        ));
        // The details of whoever issues the invoices come from the hospital: nothing is assumed.
        $settings->add(new admin_setting_configtext(
            'local_pspvinvoice/sellername',
            get_string('setting_sellername', 'local_pspvinvoice'),
            get_string('setting_seller_desc', 'local_pspvinvoice'),
            '',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvinvoice/sellertaxid',
            get_string('setting_sellertaxid', 'local_pspvinvoice'),
            get_string('setting_seller_desc', 'local_pspvinvoice'),
            '',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configtextarea(
            'local_pspvinvoice/selleraddress',
            get_string('setting_selleraddress', 'local_pspvinvoice'),
            get_string('setting_seller_desc', 'local_pspvinvoice'),
            '',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvinvoice/invoiceseries',
            get_string('setting_invoiceseries', 'local_pspvinvoice'),
            get_string('setting_series_desc', 'local_pspvinvoice'),
            'F',
            PARAM_ALPHANUM,
            8,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvinvoice/creditseries',
            get_string('setting_creditseries', 'local_pspvinvoice'),
            get_string('setting_series_desc', 'local_pspvinvoice'),
            'R',
            PARAM_ALPHANUM,
            8,
        ));
        $settings->add(new admin_setting_configtextarea(
            'local_pspvinvoice/footernote',
            get_string('setting_footernote', 'local_pspvinvoice'),
            get_string('setting_footernote_desc', 'local_pspvinvoice'),
            '',
            PARAM_TEXT,
        ));
    }
    $ADMIN->add('local_pspvinvoice', $settings);

    $ADMIN->add('local_pspvinvoice', new admin_externalpage(
        'local_pspvinvoice_invoices',
        get_string('invoices', 'local_pspvinvoice'),
        new \core\url('/local/pspvinvoice/manage/invoices.php'),
        'local/pspvinvoice:manage',
    ));
}
