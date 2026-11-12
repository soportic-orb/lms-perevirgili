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

/**
 * Runs the health checks and tells the administrators when something breaks or is fixed.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class health_alert extends \core\task\scheduled_task {
    #[\Override]
    public function get_name(): string {
        return get_string('task_health_alert', 'local_pspvcore');
    }

    #[\Override]
    public function execute(): void {
        $sent = \local_pspvcore\health\checker::notify();
        if ($sent !== '') {
            mtrace("Health notification sent to the administrators: $sent.");
        }
    }
}
