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
 * Settings and administration pages of the shop.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$pspvshopcaps = ['local/pspvshop:manageproducts', 'local/pspvshop:managecoupons', 'local/pspvshop:manageorders'];
if ($hassiteconfig || has_any_capability($pspvshopcaps, \core\context\system::instance())) {
    $ADMIN->add('localplugins', new admin_category('local_pspvshop', get_string('pluginname', 'local_pspvshop')));

    $settings = new admin_settingpage('local_pspvshop_settings', get_string('settings', 'local_pspvshop'));
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'local_pspvshop/category',
            get_string('setting_category', 'local_pspvshop'),
            get_string('setting_category_desc', 'local_pspvshop'),
            'pspv-external',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvshop/internalcategory',
            get_string('setting_internalcategory', 'local_pspvshop'),
            get_string('setting_internalcategory_desc', 'local_pspvshop'),
            'pspv-internal',
            PARAM_TEXT,
        ));
        $settings->add(new admin_setting_configtext(
            'local_pspvshop/currency',
            get_string('setting_currency', 'local_pspvshop'),
            get_string('setting_currency_desc', 'local_pspvshop'),
            'EUR',
            PARAM_ALPHA,
            4,
        ));
        // The tax treatment of the courses is the hospital's decision: nothing is assumed.
        $settings->add(new admin_setting_configtext(
            'local_pspvshop/taxrate',
            get_string('setting_taxrate', 'local_pspvshop'),
            get_string('setting_taxrate_desc', 'local_pspvshop'),
            '',
            PARAM_RAW_TRIMMED,
            6,
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_pspvshop/pricesincludetax',
            get_string('setting_pricesincludetax', 'local_pspvshop'),
            get_string('setting_pricesincludetax_desc', 'local_pspvshop'),
            1,
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_pspvshop/showtiers',
            get_string('setting_showtiers', 'local_pspvshop'),
            get_string('setting_showtiers_desc', 'local_pspvshop'),
            1,
        ));

        $accounts = [0 => get_string('none')] + \core_payment\helper::get_payment_accounts_menu(\core\context\system::instance());
        $settings->add(new admin_setting_configselect(
            'local_pspvshop/paymentaccount',
            get_string('setting_paymentaccount', 'local_pspvshop'),
            get_string('setting_paymentaccount_desc', 'local_pspvshop'),
            0,
            $accounts,
        ));
        $roles = [0 => get_string('setting_enrolrole_default', 'local_pspvshop')];
        $roles += role_fix_names(get_all_roles(), null, ROLENAME_ALIAS, true);
        $settings->add(new admin_setting_configselect(
            'local_pspvshop/enrolrole',
            get_string('setting_enrolrole', 'local_pspvshop'),
            get_string('setting_enrolrole_desc', 'local_pspvshop'),
            0,
            $roles,
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_pspvshop/testmode',
            get_string('setting_testmode', 'local_pspvshop'),
            get_string('setting_testmode_desc', 'local_pspvshop'),
            0,
        ));
    }
    $ADMIN->add('local_pspvshop', $settings);

    $ADMIN->add('local_pspvshop', new admin_externalpage(
        'local_pspvshop_products',
        get_string('products', 'local_pspvshop'),
        new \core\url('/local/pspvshop/manage/products.php'),
        'local/pspvshop:manageproducts',
    ));
    $ADMIN->add('local_pspvshop', new admin_externalpage(
        'local_pspvshop_coupons',
        get_string('coupons', 'local_pspvshop'),
        new \core\url('/local/pspvshop/manage/coupons.php'),
        'local/pspvshop:managecoupons',
    ));
    $ADMIN->add('local_pspvshop', new admin_externalpage(
        'local_pspvshop_orders',
        get_string('orders', 'local_pspvshop'),
        new \core\url('/local/pspvshop/manage/orders.php'),
        'local/pspvshop:manageorders',
    ));
}
