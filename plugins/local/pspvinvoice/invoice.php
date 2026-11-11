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

/**
 * Downloads an invoice or a credit note as a PDF.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login(null, false);

$id = required_param('id', PARAM_INT);

$invoice = $DB->get_record('local_pspvinvoice_invoice', ['id' => $id], '*', MUST_EXIST);
$system = \core\context\system::instance();
if ((int) $invoice->userid !== (int) $USER->id) {
    require_capability('local/pspvinvoice:manage', $system);
}

$PAGE->set_context($system);
$PAGE->set_url(new \core\url('/local/pspvinvoice/invoice.php', ['id' => $id]));

send_content_uncached(\local_pspvinvoice\pdf::render($invoice), $invoice->fullnumber . '.pdf');
