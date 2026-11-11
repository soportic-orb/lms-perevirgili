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

namespace local_pspvaccess\event;

/**
 * The employee list was imported.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int created: employees added.
 *      - int updated: employees changed.
 *      - int deleted: employees removed.
 *      - int skipped: rows ignored.
 * }
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class employee_list_imported extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_employee_list_imported', 'local_pspvaccess');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' imported the employee list: {$this->other['created']} created, " .
            "{$this->other['updated']} updated, {$this->other['deleted']} deleted, {$this->other['skipped']} skipped.";
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/local/pspvaccess/employees.php');
    }

    #[\Override]
    protected function validate_data() {
        parent::validate_data();
        foreach (['created', 'updated', 'deleted', 'skipped'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '$key' value must be set in other.");
            }
        }
    }
}
