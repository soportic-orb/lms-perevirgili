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

namespace local_pspvshop;

/**
 * Orders: from the cart to the enrolment.
 *
 * An order is created "pending" with a copy of every amount, so later price
 * changes never alter it. When the payment subsystem confirms the payment the
 * order becomes "paid" and the buyer is enrolled in the courses. A refund
 * unenrols the buyer.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class order_manager {
    /** @var string Waiting for payment. */
    public const PENDING = 'pending';

    /** @var string Paid: the buyer is enrolled. */
    public const PAID = 'paid';

    /** @var string Abandoned or cancelled before payment. */
    public const CANCELLED = 'cancelled';

    /** @var string Paid and later refunded: the buyer is no longer enrolled. */
    public const REFUNDED = 'refunded';

    /**
     * An order by id.
     *
     * @param int $id
     * @return \stdClass
     */
    public static function get(int $id): \stdClass {
        global $DB;

        return $DB->get_record('local_pspvshop_order', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Lines of an order.
     *
     * @param int $orderid
     * @return \stdClass[]
     */
    public static function items(int $orderid): array {
        global $DB;

        return array_values($DB->get_records('local_pspvshop_orderitem', ['orderid' => $orderid], 'id'));
    }

    /**
     * Human-readable number of an order.
     *
     * @param \stdClass $order
     * @return string
     */
    public static function number(\stdClass $order): string {
        return userdate($order->timecreated, '%Y', 99, false) . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Turns the cart of the current user into a pending order.
     *
     * Earlier pending orders of the same user are cancelled: there is one checkout at a time.
     * An order that costs nothing (a 100 % discount) is delivered straight away.
     *
     * @return \stdClass The order.
     * @throws \moodle_exception When the cart is empty.
     */
    public static function create_from_cart(): \stdClass {
        global $DB, $USER;

        $summary = cart::summary();
        if (!$summary['lines']) {
            throw new \moodle_exception('cart_empty', 'local_pspvshop');
        }
        $now = time();

        $transaction = $DB->start_delegated_transaction();
        foreach ($DB->get_records('local_pspvshop_order', ['userid' => $USER->id, 'status' => self::PENDING]) as $old) {
            self::cancel((int) $old->id);
        }
        $orderid = $DB->insert_record('local_pspvshop_order', [
            'userid' => $USER->id,
            'status' => self::PENDING,
            'currency' => money::currency(),
            'subtotal' => money::decimal($summary['subtotal']),
            'discount' => money::decimal($summary['discount']),
            'tax' => money::decimal($summary['tax']),
            'total' => money::decimal($summary['total']),
            'couponid' => $summary['coupon']->id ?? null,
            'couponcode' => $summary['coupon']->code ?? null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        foreach ($summary['lines'] as $line) {
            $DB->insert_record('local_pspvshop_orderitem', [
                'orderid' => $orderid,
                'productid' => $line['product']->id,
                'courseid' => $line['product']->courseid,
                // The cart has the name ready for HTML; the order keeps it as plain text.
                'name' => \core_text::substr(html_entity_decode($line['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, 255),
                'unitprice' => money::decimal($line['unitprice']),
                'discount' => money::decimal($line['discount']),
                'taxrate' => number_format($line['taxrate'], 2, '.', ''),
                'tax' => money::decimal($line['tax']),
                'total' => money::decimal($line['total']),
            ]);
        }
        $transaction->allow_commit();

        event\order_created::create([
            'context' => \core\context\system::instance(),
            'objectid' => $orderid,
            'relateduserid' => $USER->id,
            'other' => ['total' => money::decimal($summary['total']), 'currency' => money::currency()],
        ])->trigger();

        if ($summary['total'] === 0) {
            self::mark_paid($orderid, null, 'free');
        }
        return self::get($orderid);
    }

    /**
     * Records that an order has been paid and enrols the buyer. Safe to call twice.
     *
     * @param int $orderid
     * @param int|null $paymentid Row of the payments table, when there is one.
     * @param string $gateway Payment gateway that took the money.
     * @return bool False when the order was not waiting for payment.
     */
    public static function mark_paid(int $orderid, ?int $paymentid, string $gateway): bool {
        global $DB;

        $order = self::get($orderid);
        if ($order->status !== self::PENDING) {
            return false;
        }
        $now = time();

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('local_pspvshop_order', [
            'id' => $order->id,
            'status' => self::PAID,
            'paymentid' => $paymentid,
            'gateway' => $gateway,
            'timepaid' => $now,
            'timemodified' => $now,
        ]);
        if ($order->couponid) {
            $DB->execute('UPDATE {local_pspvshop_coupon} SET timesused = timesused + 1 WHERE id = ?', [$order->couponid]);
        }
        foreach (self::items($order->id) as $item) {
            self::enrol((int) $order->userid, (int) $item->courseid);
        }
        cart::clear((int) $order->userid);
        $transaction->allow_commit();

        event\order_paid::create([
            'context' => \core\context\system::instance(),
            'objectid' => $order->id,
            'relateduserid' => $order->userid,
            'other' => ['total' => $order->total, 'currency' => $order->currency, 'gateway' => $gateway],
        ])->trigger();
        notifier::order_paid(self::get($order->id));
        return true;
    }

    /**
     * Cancels an order that has not been paid.
     *
     * @param int $orderid
     * @return bool False when the order was not waiting for payment.
     */
    public static function cancel(int $orderid): bool {
        global $DB;

        $order = self::get($orderid);
        if ($order->status !== self::PENDING) {
            return false;
        }
        $DB->update_record('local_pspvshop_order', ['id' => $order->id, 'status' => self::CANCELLED, 'timemodified' => time()]);
        event\order_cancelled::create([
            'context' => \core\context\system::instance(),
            'objectid' => $order->id,
            'relateduserid' => $order->userid,
        ])->trigger();
        return true;
    }

    /**
     * Records that a paid order has been refunded and unenrols the buyer.
     *
     * The money itself is returned from the payment gateway: this does not move any.
     *
     * @param int $orderid
     * @return bool False when the order was not paid.
     */
    public static function refund(int $orderid): bool {
        global $DB;

        $order = self::get($orderid);
        if ($order->status !== self::PAID) {
            return false;
        }
        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('local_pspvshop_order', ['id' => $order->id, 'status' => self::REFUNDED, 'timemodified' => time()]);
        foreach (self::items($order->id) as $item) {
            self::unenrol((int) $order->userid, (int) $item->courseid);
        }
        $transaction->allow_commit();

        event\order_refunded::create([
            'context' => \core\context\system::instance(),
            'objectid' => $order->id,
            'relateduserid' => $order->userid,
            'other' => ['total' => $order->total, 'currency' => $order->currency],
        ])->trigger();
        return true;
    }

    /**
     * Enrols a buyer in a course through the manual enrolment method.
     *
     * @param int $userid
     * @param int $courseid
     */
    protected static function enrol(int $userid, int $courseid): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            return;
        }
        $plugin = enrol_get_plugin('manual');
        $instance = self::manual_instance($courseid);
        if (!$instance) {
            $plugin->add_default_instance($course);
            $instance = self::manual_instance($courseid);
        }
        $roleid = (int) get_config('local_pspvshop', 'enrolrole');
        if (!$roleid) {
            $students = get_archetype_roles('student');
            $roleid = $students ? (int) reset($students)->id : 0;
        }
        $plugin->enrol_user($instance, $userid, $roleid ?: null, time(), 0, ENROL_USER_ACTIVE);
    }

    /**
     * Removes the enrolment a purchase gave.
     *
     * @param int $userid
     * @param int $courseid
     */
    protected static function unenrol(int $userid, int $courseid): void {
        $instance = self::manual_instance($courseid);
        if ($instance) {
            enrol_get_plugin('manual')->unenrol_user($instance, $userid);
        }
    }

    /**
     * The manual enrolment instance of a course.
     *
     * @param int $courseid
     * @return \stdClass|null
     */
    protected static function manual_instance(int $courseid): ?\stdClass {
        global $DB;

        $instances = $DB->get_records('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], 'sortorder, id', '*', 0, 1);
        return $instances ? reset($instances) : null;
    }
}
