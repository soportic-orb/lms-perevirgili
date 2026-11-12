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
 * Health check: the last OTA update worked and the site can verify the next one.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ota extends check {
    /** @var int Seconds without looking for updates after which a warning is given. */
    public const STALE = 2 * DAYSECS;

    #[\Override]
    public function get_name(): string {
        return get_string('check_ota', 'local_pspvcore');
    }

    #[\Override]
    public function get_action_link(): ?\action_link {
        return new \action_link(new \core\url('/local/pspvcore/ota.php'), get_string('ota_title', 'local_pspvcore'));
    }

    #[\Override]
    public function get_result(): result {
        if (trim((string) get_config('local_pspvcore', 'otapublickey')) === '') {
            return new result(result::WARNING, get_string('check_ota_nokey', 'local_pspvcore'));
        }
        // The task that looks for updates runs every hour: a long silence means it cannot reach the releases.
        $lastcheck = (int) get_config('local_pspvcore', 'otalastcheck');
        if ($lastcheck && time() - $lastcheck > self::STALE) {
            return new result(result::WARNING, get_string('check_ota_stale', 'local_pspvcore', format_time(time() - $lastcheck)));
        }
        $history = json_decode((string) get_config('local_pspvcore', 'otahistory'), true);
        $last = is_array($history) && $history ? reset($history) : null;
        if ($last && ($last['action'] ?? '') === 'rolledback') {
            return new result(result::WARNING, get_string('check_ota_rolledback', 'local_pspvcore'));
        }
        $available = json_decode((string) get_config('local_pspvcore', 'otaavailable'), true);
        if (!empty($available['version'])) {
            return new result(result::INFO, get_string('check_ota_available', 'local_pspvcore', (string) $available['version']));
        }
        return new result(result::OK, get_string('check_ota_ok', 'local_pspvcore'));
    }
}
