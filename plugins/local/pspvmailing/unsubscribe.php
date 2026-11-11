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
 * Opting out of commercial communications from the link of an email. It needs no login: the token of
 * the link identifies the user.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvmailing\unsubscribe;

// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$userid = required_param('u', PARAM_INT);
$token = required_param('t', PARAM_ALPHANUM);
$resubscribe = optional_param('resubscribe', 0, PARAM_BOOL);

$url = new \core\url('/local/pspvmailing/unsubscribe.php', ['u' => $userid, 't' => $token]);
$PAGE->set_url($url);
$PAGE->set_context(\core\context\system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('unsubscribe', 'local_pspvmailing'));
$PAGE->set_heading(format_string($SITE->fullname));

$valid = unsubscribe::verify($userid, $token) && \core_user::get_user($userid, 'id', IGNORE_MISSING);
if (!$valid) {
    // Whoever follows a broken link gets an explanation, and nothing is changed.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('unsubscribe', 'local_pspvmailing'));
    echo $OUTPUT->notification(get_string('unsubscribe_invalid', 'local_pspvmailing'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

// The token of the link is what authorises the request, also when a mail program posts it
// for its own "unsubscribe" button (RFC 8058), which has no session.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($resubscribe) {
        unsubscribe::opt_in($userid);
    } else {
        unsubscribe::opt_out($userid);
    }
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('unsubscribe', 'local_pspvmailing'));
echo $OUTPUT->render_from_template('local_pspvmailing/unsubscribe', [
    'action' => $url->out(false),
    'optedout' => unsubscribe::is_opted_out($userid),
    'homeurl' => (new \core\url('/'))->out(false),
]);
echo $OUTPUT->footer();
