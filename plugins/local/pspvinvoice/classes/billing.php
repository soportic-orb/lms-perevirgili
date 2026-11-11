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

namespace local_pspvinvoice;

/**
 * Billing details: who an invoice is made out to.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class billing {
    /** @var string[] Fields a buyer fills in. */
    public const FIELDS = ['customertype', 'name', 'taxid', 'address', 'postcode', 'city', 'country', 'email'];

    /**
     * The billing details given for an order.
     *
     * @param int $orderid
     * @return \stdClass|null
     */
    public static function for_order(int $orderid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_pspvinvoice_billing', ['orderid' => $orderid]) ?: null;
    }

    /**
     * What to show in the form the first time: the details of the buyer's previous order, or their profile.
     *
     * @param \stdClass $user
     * @return array
     */
    public static function defaults(\stdClass $user): array {
        global $DB;

        $previous = $DB->get_records('local_pspvinvoice_billing', ['userid' => $user->id], 'timemodified DESC, id DESC', '*', 0, 1);
        if ($previous) {
            return array_intersect_key((array) reset($previous), array_flip(self::FIELDS));
        }
        return [
            'customertype' => 'person',
            'name' => fullname($user),
            'address' => (string) $user->address,
            'city' => (string) $user->city,
            'country' => $user->country ?: 'ES',
            'email' => $user->email,
        ];
    }

    /**
     * Saves the billing details of an order.
     *
     * @param int $orderid
     * @param int $userid
     * @param \stdClass $data The fields of FIELDS.
     * @return \stdClass
     */
    public static function save(int $orderid, int $userid, \stdClass $data): \stdClass {
        global $DB;

        $now = time();
        $record = [
            'orderid' => $orderid,
            'userid' => $userid,
            'customertype' => ($data->customertype ?? '') === 'company' ? 'company' : 'person',
            'name' => \core_text::substr(trim($data->name), 0, 255),
            'taxid' => \core_text::strtoupper(preg_replace('/[\s.-]+/', '', (string) $data->taxid)),
            'address' => \core_text::substr(trim($data->address), 0, 255),
            'postcode' => \core_text::substr(trim($data->postcode), 0, 15),
            'city' => \core_text::substr(trim($data->city), 0, 120),
            'country' => \core_text::strtoupper(\core_text::substr(trim($data->country), 0, 2)),
            'email' => trim((string) ($data->email ?? '')) ?: null,
            'timemodified' => $now,
        ];
        $existing = self::for_order($orderid);
        if ($existing) {
            $DB->update_record('local_pspvinvoice_billing', ['id' => $existing->id] + $record);
        } else {
            $DB->insert_record('local_pspvinvoice_billing', $record + ['timecreated' => $now]);
        }
        return self::for_order($orderid);
    }
}
