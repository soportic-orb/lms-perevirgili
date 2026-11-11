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

namespace local_pspvaccredit\task;

use local_pspvaccredit\accreditation;
use local_pspvaccredit\course_settings;

/**
 * Records the students who have come to meet the criteria of their accredited courses.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_accreditations extends \core\task\scheduled_task {
    #[\Override]
    public function get_name(): string {
        return get_string('task_sync', 'local_pspvaccredit');
    }

    #[\Override]
    public function execute(): void {
        foreach (course_settings::accredited_course_ids() as $courseid) {
            $awarded = accreditation::sync_course($courseid);
            if ($awarded) {
                mtrace("Course $courseid: $awarded new accreditation records.");
            }
        }
    }
}
