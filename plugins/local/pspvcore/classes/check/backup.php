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
 * Health check: the backup script of the server has run recently.
 *
 * The script (deploy/cloudpanel/backup.sh) leaves a small file in the backup folder every time it finishes.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup extends check {
    /** @var string Name of the file the backup script leaves when it finishes. */
    public const MARKER = 'last-backup.json';

    /** @var int Seconds after which the last backup is too old. */
    public const LIMIT = 36 * HOURSECS;

    #[\Override]
    public function get_name(): string {
        return get_string('check_backup', 'local_pspvcore');
    }

    #[\Override]
    public function get_result(): result {
        $dir = rtrim(trim((string) get_config('local_pspvcore', 'backupdir')), '/');
        if ($dir === '') {
            return new result(result::NA, get_string('check_backup_off', 'local_pspvcore'));
        }
        $marker = $dir . '/' . self::MARKER;
        $data = is_readable($marker) ? json_decode((string) file_get_contents($marker), true) : null;
        if (!is_array($data) || empty($data['time'])) {
            return new result(result::ERROR, get_string('check_backup_never', 'local_pspvcore'));
        }
        $delta = time() - (int) $data['time'];
        $summary = get_string('check_backup_last', 'local_pspvcore', format_time($delta));
        return new result($delta > self::LIMIT ? result::ERROR : result::OK, $summary);
    }
}
