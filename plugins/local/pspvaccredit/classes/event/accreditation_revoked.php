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

namespace local_pspvaccredit\event;

/**
 * The accreditation record of a student was deleted.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accreditation_revoked extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_pspvaccredit_record';
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_accreditation_revoked', 'local_pspvaccredit');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '{$this->userid}' deleted the accreditation record '{$this->objectid}' of the user with id "
            . "'{$this->relateduserid}' in the course with id '{$this->courseid}'.";
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/local/pspvaccredit/index.php', ['id' => $this->courseid]);
    }
}
