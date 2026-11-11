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

namespace local_pspvshop\local;

/**
 * Installation steps of the shop.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /** @var string Shortname of the sales manager role created by local_pspvaccess. */
    public const SALES_MANAGER = 'pspvsalesmanager';

    /** @var string[] What a sales manager can do in the shop. Simulating payments stays with administrators. */
    public const SALES_CAPABILITIES = [
        'local/pspvshop:manageproducts',
        'local/pspvshop:managecoupons',
        'local/pspvshop:manageorders',
    ];

    /**
     * Gives the sales manager role, if it exists, the capabilities of the shop.
     */
    public static function grant_sales_manager(): void {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => self::SALES_MANAGER]);
        if (!$roleid) {
            return;
        }
        // During installation the capabilities of the plugin are registered after db/install.php runs.
        if (!get_capability_info(self::SALES_CAPABILITIES[0])) {
            update_capabilities('local_pspvshop');
        }
        $system = \core\context\system::instance();
        foreach (self::SALES_CAPABILITIES as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $system->id, true);
        }
    }
}
