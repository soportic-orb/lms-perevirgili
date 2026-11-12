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
 * Theme settings.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Moodle creates the page hidden: a visible one replaces it.
    $settings = new admin_settingpage('themesettingpspv', new lang_string('configtitle', 'theme_pspv'));

    $setting = new admin_setting_configstoredfile(
        'theme_pspv/logo',
        get_string('setting_logo', 'theme_pspv'),
        get_string('setting_logo_desc', 'theme_pspv'),
        'logo',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.svg', '.png', '.jpg', '.webp']],
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'theme_pspv/owner',
        get_string('setting_owner', 'theme_pspv'),
        get_string('setting_owner_desc', 'theme_pspv'),
        'Parc Sanitari Pere Virgili',
        PARAM_TEXT,
    );
    $settings->add($setting);

    // Colours: the two blues of the hospital's logo.
    $setting = new admin_setting_configcolourpicker(
        'theme_pspv/brandcolor',
        get_string('setting_brandcolor', 'theme_pspv'),
        get_string('setting_brandcolor_desc', 'theme_pspv'),
        '#0074d1',
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configcolourpicker(
        'theme_pspv/secondarycolor',
        get_string('setting_secondarycolor', 'theme_pspv'),
        get_string('setting_secondarycolor_desc', 'theme_pspv'),
        '#009de4',
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Front page.
    $settings->add(new admin_setting_heading(
        'theme_pspv/frontpageheading',
        get_string('setting_frontpage', 'theme_pspv'),
        '',
    ));
    $settings->add(new admin_setting_configtext(
        'theme_pspv/herotitle',
        get_string('setting_herotitle', 'theme_pspv'),
        get_string('setting_herotext_desc', 'theme_pspv'),
        '',
        PARAM_TEXT,
    ));
    $settings->add(new admin_setting_configtextarea(
        'theme_pspv/herolead',
        get_string('setting_herolead', 'theme_pspv'),
        get_string('setting_herotext_desc', 'theme_pspv'),
        '',
        PARAM_TEXT,
    ));
    $settings->add(new admin_setting_configcheckbox(
        'theme_pspv/showwhy',
        get_string('setting_showwhy', 'theme_pspv'),
        get_string('setting_showwhy_desc', 'theme_pspv'),
        1,
    ));
    $settings->add(new admin_setting_configtext(
        'theme_pspv/whytitle',
        get_string('setting_whytitle', 'theme_pspv'),
        get_string('setting_herotext_desc', 'theme_pspv'),
        '',
        PARAM_TEXT,
    ));
    $settings->add(new admin_setting_configtextarea(
        'theme_pspv/whylead',
        get_string('setting_whylead', 'theme_pspv'),
        get_string('setting_herotext_desc', 'theme_pspv'),
        '',
        PARAM_TEXT,
    ));
    foreach (array_keys(\theme_pspv\output\frontpage::REASON_ICONS) as $index) {
        $number = $index + 1;
        $settings->add(new admin_setting_configtext(
            "theme_pspv/why{$number}title",
            get_string('setting_whyreasontitle', 'theme_pspv', $number),
            get_string('setting_herotext_desc', 'theme_pspv'),
            '',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configtextarea(
            "theme_pspv/why{$number}text",
            get_string('setting_whyreasontext', 'theme_pspv', $number),
            get_string('setting_herotext_desc', 'theme_pspv'),
            '',
            PARAM_TEXT,
        ));
    }
    $settings->add(new admin_setting_configtext(
        'theme_pspv/cataloguecategory',
        get_string('setting_cataloguecategory', 'theme_pspv'),
        get_string('setting_cataloguecategory_desc', 'theme_pspv'),
        \theme_pspv\local\catalogue::DEFAULT_CATEGORY,
        PARAM_TEXT,
    ));
    $settings->add(new admin_setting_configtext(
        'theme_pspv/cataloguecount',
        get_string('setting_cataloguecount', 'theme_pspv'),
        '',
        6,
        PARAM_INT,
    ));

    // Footer.
    $settings->add(new admin_setting_heading(
        'theme_pspv/footerheading',
        get_string('setting_footer', 'theme_pspv'),
        '',
    ));
    $settings->add(new admin_setting_confightmleditor(
        'theme_pspv/footercontact',
        get_string('setting_footercontact', 'theme_pspv'),
        get_string('setting_footercontact_desc', 'theme_pspv'),
        '',
    ));
    $settings->add(new admin_setting_configcheckbox(
        'theme_pspv/footerpolicies',
        get_string('setting_footerpolicies', 'theme_pspv'),
        get_string('setting_footerpolicies_desc', 'theme_pspv'),
        1,
    ));
}
