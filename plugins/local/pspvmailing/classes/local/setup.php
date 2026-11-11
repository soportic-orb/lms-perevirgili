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

namespace local_pspvmailing\local;

/**
 * Installation steps of the mailing plugin.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /** @var string[] Shortnames of the roles of local_pspvaccess that send campaigns. */
    public const ROLES = ['pspvsalesmanager', 'pspvtrainingmanager'];

    /**
     * Lets the sales and training managers, if those roles exist, send campaigns.
     */
    public static function grant_managers(): void {
        global $DB;

        $system = \core\context\system::instance();
        foreach (self::ROLES as $shortname) {
            $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
            if (!$roleid) {
                continue;
            }
            // During installation the capabilities of the plugin are registered after db/install.php runs.
            if (!get_capability_info('local/pspvmailing:manage')) {
                update_capabilities('local_pspvmailing');
            }
            assign_capability('local/pspvmailing:manage', CAP_ALLOW, $roleid, $system->id, true);
        }
    }
}
