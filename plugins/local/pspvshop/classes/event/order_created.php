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

namespace local_pspvshop\event;

/**
 * An order was placed and is waiting for payment.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class order_created extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_pspvshop_order';
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_order_created', 'local_pspvshop');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->relateduserid' placed the order with id '$this->objectid'.";
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/local/pspvshop/manage/orders.php', ['id' => $this->objectid]);
    }
}
