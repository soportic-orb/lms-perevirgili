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
 * Gives the sales manager role access to the invoices.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs after the tables of the plugin are created.
 */
function xmldb_local_pspvinvoice_install() {
    global $DB;

    $roleid = $DB->get_field('role', 'id', ['shortname' => \local_pspvshop\local\setup::SALES_MANAGER]);
    if ($roleid) {
        // During installation the capabilities of the plugin are registered after this file runs.
        update_capabilities('local_pspvinvoice');
        assign_capability('local/pspvinvoice:manage', CAP_ALLOW, $roleid, \core\context\system::instance()->id, true);
    }
}
