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

namespace local_pspvaccess\task;

use local_pspvaccess\worker_manager;

/**
 * Re-applies the worker policy to every user, so those who stop being workers
 * leave the cohort even if they never log in again.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resync_workers extends \core\task\scheduled_task {
    #[\Override]
    public function get_name(): string {
        return get_string('task_resync', 'local_pspvaccess');
    }

    #[\Override]
    public function execute(): void {
        $counts = worker_manager::resync_all(worker_manager::ORIGIN_TASK);
        mtrace("Workers cohort: {$counts['checked']} checked, {$counts['added']} added, {$counts['removed']} removed.");
    }
}
