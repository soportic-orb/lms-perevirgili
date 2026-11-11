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

namespace local_pspvshop\local;

/**
 * Common set-up of the pages of the shop.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page {
    /**
     * Prepares a public page of the shop: visitors are welcome unless the site forces everybody to log in.
     *
     * @param string $path Path of the page, such as "/local/pspvshop/index.php".
     * @param array $params Query parameters of the page.
     * @param string $title Title of the page.
     */
    public static function setup(string $path, array $params, string $title): void {
        global $CFG, $PAGE, $SITE;

        if (!empty($CFG->forcelogin)) {
            require_login();
        }
        $PAGE->set_context(\core\context\system::instance());
        $PAGE->set_url(new \core\url($path, $params));
        $PAGE->set_pagelayout('standard');
        $PAGE->set_title($title . ' | ' . format_string($SITE->shortname));
        $PAGE->set_heading($title);
        $PAGE->add_body_class('local-pspvshop');
    }

    /**
     * Requires an account of one's own: guests are sent to log in and come back.
     */
    public static function require_account(): void {
        global $PAGE, $SESSION;

        if (!isloggedin() || isguestuser()) {
            $SESSION->wantsurl = $PAGE->url->out(false);
            redirect(get_login_url());
        }
    }

    /**
     * Status of an order as shown to people.
     *
     * @param string $status
     * @return array Label and Bootstrap colour.
     */
    public static function status(string $status): array {
        $colours = ['pending' => 'warning', 'paid' => 'success', 'cancelled' => 'secondary', 'refunded' => 'info'];
        return [
            'label' => get_string('status_' . $status, 'local_pspvshop'),
            'colour' => $colours[$status] ?? 'secondary',
        ];
    }

    /**
     * An order as the templates show it.
     *
     * @param \stdClass $order
     * @param bool $withbuyer Whether to include who bought it.
     * @return array
     */
    public static function order(\stdClass $order, bool $withbuyer = false): array {
        $currency = $order->currency;
        $lines = [];
        foreach (\local_pspvshop\order_manager::items($order->id) as $item) {
            $lines[] = [
                'name' => $item->name,
                'unitprice' => \local_pspvshop\money::format(\local_pspvshop\money::cents($item->unitprice), $currency),
                'hasdiscount' => \local_pspvshop\money::cents($item->discount) > 0,
                'discount' => \local_pspvshop\money::format(\local_pspvshop\money::cents($item->discount), $currency),
                'total' => \local_pspvshop\money::format(\local_pspvshop\money::cents($item->total), $currency),
                'courseurl' => (new \core\url('/course/view.php', ['id' => $item->courseid]))->out(false),
            ];
        }
        $data = [
            'id' => (int) $order->id,
            'number' => \local_pspvshop\order_manager::number($order),
            'date' => userdate($order->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
            'status' => self::status($order->status),
            'ispending' => $order->status === \local_pspvshop\order_manager::PENDING,
            'ispaid' => $order->status === \local_pspvshop\order_manager::PAID,
            'lines' => $lines,
            'hasdiscount' => \local_pspvshop\money::cents($order->discount) > 0,
            'discount' => \local_pspvshop\money::format(\local_pspvshop\money::cents($order->discount), $currency),
            'couponcode' => (string) $order->couponcode,
            'hastax' => \local_pspvshop\money::cents($order->tax) > 0,
            'tax' => \local_pspvshop\money::format(\local_pspvshop\money::cents($order->tax), $currency),
            'total' => \local_pspvshop\money::format(\local_pspvshop\money::cents($order->total), $currency),
            'url' => (new \core\url('/local/pspvshop/orders.php', ['id' => $order->id]))->out(false),
            'links' => self::order_links($order),
        ];
        $data['haslinks'] = !empty($data['links']);
        if ($withbuyer) {
            $user = \core_user::get_user($order->userid);
            $data['buyer'] = $user ? fullname($user) : get_string('unknownuser');
            $data['buyeremail'] = $user && !$user->deleted ? $user->email : '';
        }
        return $data;
    }

    /**
     * Links other plugins add to an order, such as its invoice.
     *
     * @param \stdClass $order
     * @return array[] Each with url and text.
     */
    protected static function order_links(\stdClass $order): array {
        $links = [];
        foreach (get_plugins_with_function('pspvshop_order_links') as $plugins) {
            foreach ($plugins as $function) {
                foreach ((array) $function($order) as $link) {
                    $links[] = ['url' => (string) $link['url'], 'text' => (string) $link['text']];
                }
            }
        }
        return $links;
    }
}
