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
 * Upgrade steps of local_pspvaccess.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs the upgrade steps between the installed version and the current one.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_pspvaccess_upgrade($oldversion) {
    if ($oldversion < 2026111700) {
        // The public of the paid training is now called "citizens", not "external".
        \local_pspvaccess\local\structure::apply_citizen_wording();
        upgrade_plugin_savepoint(true, 2026111700, 'local', 'pspvaccess');
    }

    return true;
}
