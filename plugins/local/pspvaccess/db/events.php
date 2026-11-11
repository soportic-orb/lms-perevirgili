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
 * Event observers of local_pspvaccess.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => \core\event\user_loggedin::class,
        'callback' => \local_pspvaccess\observer::class . '::user_loggedin',
    ],
    [
        'eventname' => \core\event\user_created::class,
        'callback' => \local_pspvaccess\observer::class . '::user_changed',
    ],
    [
        'eventname' => \core\event\user_updated::class,
        'callback' => \local_pspvaccess\observer::class . '::user_changed',
    ],
    [
        'eventname' => \core\event\cohort_member_added::class,
        'callback' => \local_pspvaccess\observer::class . '::cohort_member_added',
    ],
    [
        'eventname' => \core\event\cohort_member_removed::class,
        'callback' => \local_pspvaccess\observer::class . '::cohort_member_removed',
    ],
];
