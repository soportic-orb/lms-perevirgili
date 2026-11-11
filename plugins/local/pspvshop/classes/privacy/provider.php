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

namespace local_pspvshop\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_pspvshop\order_manager;

/**
 * Privacy provider: carts and orders.
 *
 * Carts and unpaid orders are deleted on request. Paid and refunded orders are
 * kept: they are the record of a sale, which the seller is obliged to keep.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_pspvshop_cartitem', [
            'userid' => 'privacy:metadata:cartitem:userid',
            'productid' => 'privacy:metadata:cartitem:productid',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:cartitem');
        $collection->add_database_table('local_pspvshop_order', [
            'userid' => 'privacy:metadata:order:userid',
            'status' => 'privacy:metadata:order:status',
            'total' => 'privacy:metadata:order:total',
            'couponcode' => 'privacy:metadata:order:couponcode',
            'gateway' => 'privacy:metadata:order:gateway',
            'timecreated' => 'privacy:metadata:timecreated',
            'timepaid' => 'privacy:metadata:order:timepaid',
        ], 'privacy:metadata:order');
        $collection->add_database_table('local_pspvshop_orderitem', [
            'name' => 'privacy:metadata:orderitem:name',
            'total' => 'privacy:metadata:orderitem:total',
        ], 'privacy:metadata:orderitem');
        $collection->add_subsystem_link('core_payment', [], 'privacy:metadata:payment');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if (
            $DB->record_exists('local_pspvshop_cartitem', ['userid' => $userid])
            || $DB->record_exists('local_pspvshop_order', ['userid' => $userid])
        ) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context() instanceof \core\context\system) {
            $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvshop_cartitem}', []);
            $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvshop_order}', []);
        }
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $system = \core\context\system::instance();
        if (!in_array($system->id, $contextlist->get_contextids())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $path = [get_string('pluginname', 'local_pspvshop')];

        $orders = [];
        foreach ($DB->get_records('local_pspvshop_order', ['userid' => $userid], 'id') as $order) {
            $lines = [];
            foreach (order_manager::items($order->id) as $item) {
                $lines[] = (object) ['name' => $item->name, 'total' => $item->total];
            }
            $orders[] = (object) [
                'number' => order_manager::number($order),
                'status' => $order->status,
                'total' => $order->total,
                'currency' => $order->currency,
                'couponcode' => $order->couponcode,
                'gateway' => $order->gateway,
                'timecreated' => transform::datetime($order->timecreated),
                'timepaid' => $order->timepaid ? transform::datetime($order->timepaid) : null,
                'lines' => $lines,
            ];
        }
        if ($orders) {
            writer::with_context($system)->export_data(
                array_merge($path, [get_string('orders', 'local_pspvshop')]),
                (object) ['orders' => $orders],
            );
        }

        $cart = $DB->get_fieldset_sql(
            "SELECT c.fullname
               FROM {local_pspvshop_cartitem} i
               JOIN {local_pspvshop_product} p ON p.id = i.productid
               JOIN {course} c ON c.id = p.courseid
              WHERE i.userid = ?",
            [$userid],
        );
        if ($cart) {
            writer::with_context($system)->export_data(
                array_merge($path, [get_string('cart', 'local_pspvshop')]),
                (object) ['courses' => $cart],
            );
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \core\context\system) {
            foreach ($DB->get_fieldset_sql('SELECT DISTINCT userid FROM {local_pspvshop_cartitem}') as $userid) {
                self::delete_user((int) $userid);
            }
            foreach ($DB->get_fieldset_sql('SELECT DISTINCT userid FROM {local_pspvshop_order}') as $userid) {
                self::delete_user((int) $userid);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (in_array(\core\context\system::instance()->id, $contextlist->get_contextids())) {
            self::delete_user((int) $contextlist->get_user()->id);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context() instanceof \core\context\system) {
            foreach ($userlist->get_userids() as $userid) {
                self::delete_user((int) $userid);
            }
        }
    }

    /**
     * Deletes the cart and the unpaid orders of a user. Records of sales are kept.
     *
     * @param int $userid
     */
    protected static function delete_user(int $userid): void {
        global $DB;

        $DB->delete_records('local_pspvshop_cartitem', ['userid' => $userid]);
        $unpaid = $DB->get_fieldset_select(
            'local_pspvshop_order',
            'id',
            'userid = ? AND status IN (?, ?)',
            [$userid, order_manager::PENDING, order_manager::CANCELLED],
        );
        if ($unpaid) {
            $DB->delete_records_list('local_pspvshop_orderitem', 'orderid', $unpaid);
            $DB->delete_records_list('local_pspvshop_order', 'id', $unpaid);
        }
    }
}
