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

namespace local_pspvcore\event;

/**
 * An OTA update was applied.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string version: version the site is on now.
 *      - string previous: version it was on before ('' if none).
 * }
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ota_applied extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_ota_applied', 'local_pspvcore');
    }

    #[\Override]
    public function get_description() {
        $from = $this->other['previous'] !== '' ? "from '{$this->other['previous']}'" : 'for the first time';
        return "The user with id '$this->userid' applied the OTA update '{$this->other['version']}' $from.";
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/local/pspvcore/ota.php');
    }

    #[\Override]
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['version']) || !isset($this->other['previous'])) {
            throw new \coding_exception('The \'version\' and \'previous\' values must be set in other.');
        }
    }
}
