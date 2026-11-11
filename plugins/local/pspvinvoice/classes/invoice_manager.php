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

use local_pspvshop\money;
use local_pspvshop\order_manager;

/**
 * Issues invoices and credit notes.
 *
 * An invoice is issued when an order is paid and a credit note when it is
 * refunded. Both keep their own copy of the seller, the buyer and the lines, and
 * are never changed afterwards: a mistake is corrected with a credit note.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class invoice_manager {
    /** @var string An invoice. */
    public const INVOICE = 'invoice';

    /** @var string A credit note, which cancels an invoice. */
    public const CREDIT = 'credit';

    /**
     * Whether invoices are issued.
     *
     * @return bool
     */
    public static function enabled(): bool {
        $enabled = get_config('local_pspvinvoice', 'enabled');
        return $enabled === false || !empty($enabled);
    }

    /**
     * The invoice of an order.
     *
     * @param int $orderid
     * @return \stdClass|null
     */
    public static function invoice_for_order(int $orderid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_pspvinvoice_invoice', ['orderid' => $orderid, 'doctype' => self::INVOICE]) ?: null;
    }

    /**
     * The credit note of an invoice.
     *
     * @param int $invoiceid
     * @return \stdClass|null
     */
    public static function credit_for_invoice(int $invoiceid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_pspvinvoice_invoice', ['refinvoiceid' => $invoiceid, 'doctype' => self::CREDIT]) ?: null;
    }

    /**
     * Issues the invoice of a paid order. Safe to call twice.
     *
     * Orders that cost nothing get no invoice.
     *
     * @param int $orderid
     * @return \stdClass|null The invoice; null when none is due.
     */
    public static function issue_for_order(int $orderid): ?\stdClass {
        if (!self::enabled()) {
            return null;
        }
        $existing = self::invoice_for_order($orderid);
        if ($existing) {
            return $existing;
        }
        $order = order_manager::get($orderid);
        if ($order->status !== order_manager::PAID || money::cents($order->total) === 0) {
            return null;
        }

        $lines = [];
        foreach (order_manager::items($orderid) as $item) {
            $total = money::cents($item->total);
            $tax = money::cents($item->tax);
            $lines[] = [
                'description' => $item->name,
                'net' => $total - $tax,
                'taxrate' => (float) $item->taxrate,
                'tax' => $tax,
                'total' => $total,
            ];
        }
        $billing = billing::for_order($orderid);
        $buyer = $billing
            ? array_intersect_key((array) $billing, array_flip(billing::FIELDS))
            : self::buyer_from_profile((int) $order->userid);

        $invoice = self::issue(self::INVOICE, $order, $lines, $buyer, null);
        notifier::invoice_issued($invoice);
        return $invoice;
    }

    /**
     * Issues the credit note that cancels the invoice of a refunded order. Safe to call twice.
     *
     * @param int $orderid
     * @return \stdClass|null The credit note; null when the order has no invoice or is not refunded.
     */
    public static function credit_for_order(int $orderid): ?\stdClass {
        $invoice = self::invoice_for_order($orderid);
        if (!$invoice) {
            return null;
        }
        $order = order_manager::get($orderid);
        if ($order->status !== order_manager::REFUNDED) {
            return null;
        }
        $existing = self::credit_for_invoice((int) $invoice->id);
        if ($existing) {
            return $existing;
        }
        $lines = [];
        foreach (json_decode($invoice->doclines, true) as $line) {
            foreach (['net', 'tax', 'total'] as $amount) {
                $line[$amount] = -$line[$amount];
            }
            $lines[] = $line;
        }
        return self::issue(self::CREDIT, $order, $lines, json_decode($invoice->billing, true), $invoice);
    }

    /**
     * Stores a document with the next number of its series and reports it to the fiscal system.
     *
     * @param string $doctype INVOICE or CREDIT.
     * @param \stdClass $order
     * @param array[] $lines Each with description, net, taxrate, tax and total (cents).
     * @param array $buyer Billing details.
     * @param \stdClass|null $corrects The invoice a credit note cancels.
     * @return \stdClass The document.
     */
    protected static function issue(
        string $doctype,
        \stdClass $order,
        array $lines,
        array $buyer,
        ?\stdClass $corrects,
    ): \stdClass {
        global $DB, $USER;

        $now = time();
        $year = (int) userdate($now, '%Y', 99, false);
        $series = self::series($doctype);

        // One document at a time per series, so numbers are correlative with no gaps.
        $lock = \core\lock\lock_config::get_lock_factory('local_pspvinvoice')->get_lock('series_' . $series . '_' . $year, 30);
        if (!$lock) {
            throw new \moodle_exception('error_numbering', 'local_pspvinvoice');
        }
        try {
            $number = 1 + (int) $DB->get_field_sql(
                'SELECT MAX(docnumber) FROM {local_pspvinvoice_invoice} WHERE series = ? AND docyear = ?',
                [$series, $year],
            );
            $id = $DB->insert_record('local_pspvinvoice_invoice', [
                'doctype' => $doctype,
                'series' => $series,
                'docyear' => $year,
                'docnumber' => $number,
                'fullnumber' => sprintf('%s-%d-%04d', $series, $year, $number),
                'orderid' => $order->id,
                'userid' => $order->userid,
                'refinvoiceid' => $corrects->id ?? null,
                'currency' => $order->currency,
                'subtotal' => money::decimal(array_sum(array_column($lines, 'net'))),
                'tax' => money::decimal(array_sum(array_column($lines, 'tax'))),
                'total' => money::decimal(array_sum(array_column($lines, 'total'))),
                'seller' => json_encode(self::seller()),
                'billing' => json_encode($buyer),
                'doclines' => json_encode($lines),
                'issuerid' => isloggedin() && !isguestuser() ? $USER->id : null,
                'timeissued' => $now,
            ]);
        } finally {
            $lock->release();
        }
        $invoice = $DB->get_record('local_pspvinvoice_invoice', ['id' => $id], '*', MUST_EXIST);

        $event = $doctype === self::CREDIT ? event\credit_note_issued::class : event\invoice_issued::class;
        $event::create([
            'context' => \core\context\system::instance(),
            'objectid' => $invoice->id,
            'relateduserid' => $invoice->userid,
            'other' => ['number' => $invoice->fullnumber, 'total' => $invoice->total, 'currency' => $invoice->currency],
        ])->trigger();

        $result = fiscal\none::resolve()->submit($invoice);
        if ($result->status !== fiscal\result::NONE) {
            $DB->update_record('local_pspvinvoice_invoice', [
                'id' => $invoice->id,
                'fiscalstatus' => $result->status,
                'fiscalref' => $result->reference === null ? null : \core_text::substr($result->reference, 0, 255),
            ]);
            $invoice = $DB->get_record('local_pspvinvoice_invoice', ['id' => $id], '*', MUST_EXIST);
        }
        return $invoice;
    }

    /**
     * Series of a kind of document.
     *
     * @param string $doctype INVOICE or CREDIT.
     * @return string
     */
    public static function series(string $doctype): string {
        $setting = $doctype === self::CREDIT ? 'creditseries' : 'invoiceseries';
        $series = preg_replace('/[^A-Za-z0-9]/', '', (string) get_config('local_pspvinvoice', $setting));
        return $series !== '' ? \core_text::strtoupper($series) : ($doctype === self::CREDIT ? 'R' : 'F');
    }

    /**
     * The details of the seller, as configured.
     *
     * @return string[] name, taxid, address and note.
     */
    public static function seller(): array {
        $config = get_config('local_pspvinvoice');
        return [
            'name' => trim((string) ($config->sellername ?? '')),
            'taxid' => trim((string) ($config->sellertaxid ?? '')),
            'address' => trim((string) ($config->selleraddress ?? '')),
            'note' => trim((string) ($config->footernote ?? '')),
        ];
    }

    /**
     * Billing details taken from the profile of a buyer who was not asked for any.
     *
     * @param int $userid
     * @return array
     */
    protected static function buyer_from_profile(int $userid): array {
        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        return [
            'customertype' => 'person',
            'name' => fullname($user),
            'taxid' => '',
            'address' => (string) $user->address,
            'postcode' => '',
            'city' => (string) $user->city,
            'country' => (string) $user->country,
            'email' => $user->email,
        ];
    }
}
