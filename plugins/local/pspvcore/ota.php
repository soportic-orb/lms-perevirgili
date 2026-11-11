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
 * OTA updates: check, apply and roll back.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvcore\ota\updater;
use local_pspvcore\output\ota_page;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_pspvcore_ota');
require_capability('local/pspvcore:manageupdates', \core\context\system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$pageurl = new \core\url('/local/pspvcore/ota.php');
$updater = new updater();

if ($action !== '') {
    require_sesskey();

    if (in_array($action, ['apply', 'rollback'], true) && !$confirm) {
        $available = $updater->available();
        $message = $action === 'apply'
            ? get_string('ota_confirm_apply', 'local_pspvcore', s($available['version'] ?? ''))
            : get_string('ota_confirm_rollback', 'local_pspvcore');
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            $message,
            new \core\url($pageurl, ['action' => $action, 'confirm' => 1, 'sesskey' => sesskey()]),
            $pageurl,
        );
        echo $OUTPUT->footer();
        exit;
    }

    try {
        switch ($action) {
            case 'check':
                $release = $updater->check();
                $message = $release
                    ? get_string('ota_available', 'local_pspvcore', s($release['version']))
                    : get_string('ota_uptodate', 'local_pspvcore');
                redirect($pageurl, $message, null, \core\output\notification::NOTIFY_INFO);
                // The redirect ends the request.
            case 'apply':
                \core\session\manager::write_close();
                $result = $updater->apply();
                $message = get_string('ota_applied', 'local_pspvcore', s($result['version']));
                // Moodle's own upgrade page runs the database upgrade of the plugins that changed.
                $next = $result['needsupgrade'] ? new \core\url('/admin/index.php') : $pageurl;
                redirect($next, $message, null, \core\output\notification::NOTIFY_SUCCESS);
                // The redirect ends the request.
            case 'rollback':
                \core\session\manager::write_close();
                $updater->rollback();
                redirect(
                    $pageurl,
                    get_string('ota_rolledback', 'local_pspvcore'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS,
                );
                // The redirect ends the request.
            default:
                redirect($pageurl);
        }
    } catch (\moodle_exception $e) {
        redirect($pageurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('ota_title', 'local_pspvcore'));
echo $OUTPUT->render_from_template('local_pspvcore/ota_page', (new ota_page($updater))->export_for_template($OUTPUT));
echo $OUTPUT->footer();
