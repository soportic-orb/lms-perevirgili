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
 * Callbacks of local_pspvaccredit.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the accreditation page to the navigation of a course.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_pspvaccredit_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context): void {
    if (!has_any_capability(['local/pspvaccredit:viewreport', 'local/pspvaccredit:markattendance'], $context)) {
        return;
    }
    $navigation->add(
        get_string('accreditation', 'local_pspvaccredit'),
        new \core\url('/local/pspvaccredit/index.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_pspvaccredit',
        new pix_icon('i/badge', ''),
    );
}
