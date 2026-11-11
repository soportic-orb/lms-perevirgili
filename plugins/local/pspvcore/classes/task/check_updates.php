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

namespace local_pspvcore\task;

use local_pspvcore\ota\updater;

/**
 * Looks for a new OTA release. It never applies it: an administrator does.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_updates extends \core\task\scheduled_task {
    #[\Override]
    public function get_name(): string {
        return get_string('task_checkupdates', 'local_pspvcore');
    }

    #[\Override]
    public function execute(): void {
        $updater = new updater();
        if (!$updater->is_configured()) {
            mtrace('OTA: no public key configured, nothing to check.');
            return;
        }
        $release = $updater->check();
        mtrace($release ? "OTA: version {$release['version']} is available." : 'OTA: up to date.');
    }
}
