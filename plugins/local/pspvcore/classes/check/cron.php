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

namespace local_pspvcore\check;

use core\check\check;
use core\check\result;

/**
 * Health check: the Moodle cron is running.
 *
 * Without cron nothing scheduled happens: no emails, no OTA checks, no campaigns, no certificates.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron extends check {
    /** @var int Seconds without cron after which the campus is considered broken. */
    public const LIMIT = 30 * MINSECS;

    #[\Override]
    public function get_name(): string {
        return get_string('check_cron', 'local_pspvcore');
    }

    #[\Override]
    public function get_result(): result {
        $last = (int) get_config('tool_task', 'lastcronstart');
        if (!$last) {
            return new result(result::ERROR, get_string('check_cron_never', 'local_pspvcore'));
        }
        $delta = time() - $last;
        $summary = get_string('check_cron_last', 'local_pspvcore', format_time($delta));
        return new result($delta > self::LIMIT ? result::ERROR : result::OK, $summary);
    }
}
