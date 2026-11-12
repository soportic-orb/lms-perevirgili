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
 * Health check: there is free space where Moodle keeps its files.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class disk extends check {
    /** @var float Share of free space under which a warning is given. */
    public const WARNING = 0.15;

    /** @var float Share of free space under which the campus is considered at risk. */
    public const ERROR = 0.05;

    #[\Override]
    public function get_name(): string {
        return get_string('check_disk', 'local_pspvcore');
    }

    #[\Override]
    public function get_result(): result {
        global $CFG;

        $total = @disk_total_space($CFG->dataroot);
        $free = @disk_free_space($CFG->dataroot);
        if (!$total || $free === false) {
            return new result(result::UNKNOWN, get_string('check_disk_unknown', 'local_pspvcore'));
        }
        return self::evaluate((float) $free, (float) $total);
    }

    /**
     * Rates an amount of free space.
     *
     * @param float $free Bytes.
     * @param float $total Bytes.
     * @return result
     */
    public static function evaluate(float $free, float $total): result {
        $share = $free / $total;
        $summary = get_string('check_disk_free', 'local_pspvcore', (object) [
            'free' => display_size($free),
            'percent' => format_float($share * 100, 0),
        ]);
        $status = $share < self::ERROR ? result::ERROR : ($share < self::WARNING ? result::WARNING : result::OK);
        return new result($status, $summary);
    }
}
