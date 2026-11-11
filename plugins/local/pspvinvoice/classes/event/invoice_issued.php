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

namespace local_pspvinvoice\event;

/**
 * An invoice was issued.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string number: full number of the document.
 *      - string total: amount of the document.
 *      - string currency: currency of the document.
 * }
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class invoice_issued extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_pspvinvoice_invoice';
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_invoice_issued', 'local_pspvinvoice');
    }

    #[\Override]
    public function get_description() {
        return "The invoice '{$this->other['number']}' with id '$this->objectid' was issued " .
            "to the user with id '$this->relateduserid'.";
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/local/pspvinvoice/invoice.php', ['id' => $this->objectid]);
    }
}
