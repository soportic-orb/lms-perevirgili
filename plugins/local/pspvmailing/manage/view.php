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
 * A campaign: what it says, who gets it, and the actions to test, schedule, cancel or delete it.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvmailing\campaign;
use local_pspvmailing\segment;
use local_pspvmailing\sender;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$url = new \core\url('/local/pspvmailing/manage/view.php', ['id' => $id]);
admin_externalpage_setup('local_pspvmailing_campaigns', '', null, $url);
require_capability('local/pspvmailing:manage', \core\context\system::instance());

$record = campaign::get($id);
$list = new \core\url('/local/pspvmailing/manage/index.php');
$success = \core\output\notification::NOTIFY_SUCCESS;

if ($action !== '') {
    require_sesskey();
    switch ($action) {
        case 'test':
            $sent = sender::send($record, $USER, true);
            redirect(
                $url,
                get_string($sent ? 'test_sent' : 'test_failed', 'local_pspvmailing', $USER->email),
                null,
                $sent ? $success : \core\output\notification::NOTIFY_ERROR,
            );
            break;
        case 'sendnow':
            campaign::schedule($id, time());
            redirect($url, get_string('campaign_queued', 'local_pspvmailing'), null, $success);
            break;
        case 'cancel':
            campaign::cancel($id);
            redirect($url, get_string('campaign_cancelled', 'local_pspvmailing'), null, $success);
            break;
        case 'delete':
            campaign::delete($id);
            redirect($list, get_string('campaign_deleted', 'local_pspvmailing'), null, $success);
            break;
    }
    redirect($url);
}

$isdraft = $record->status === campaign::DRAFT;
$schedule = null;
if ($isdraft) {
    $schedule = new \local_pspvmailing\form\schedule_form($url);
    $schedule->set_data(['id' => $id, 'timescheduled' => time() + HOURSECS]);
    if ($data = $schedule->get_data()) {
        campaign::schedule($id, (int) $data->timescheduled);
        redirect($url, get_string('campaign_scheduled', 'local_pspvmailing'), null, $success);
    }
}

$criteria = campaign::criteria($record);
$counts = campaign::counts($id);
$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
[$subject, $html] = sender::build($record, $USER);
$actionurl = fn(string $name) => (new \core\url($url, ['action' => $name, 'sesskey' => sesskey()]))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($record->name));
echo $OUTPUT->render_from_template('local_pspvmailing/campaign', [
    'status' => get_string('status_' . $record->status, 'local_pspvmailing'),
    'isdraft' => $isdraft,
    'isscheduled' => $record->status === campaign::SCHEDULED,
    'issending' => $record->status === campaign::SENDING,
    'hasstarted' => $record->timestarted > 0,
    'scheduled' => $record->timescheduled ? userdate($record->timescheduled, $dateformat) : '',
    'finished' => $record->timefinished ? userdate($record->timefinished, $dateformat) : '',
    'commercial' => (bool) $record->commercial,
    'segment' => segment::describe($criteria),
    'estimate' => segment::count($criteria, (bool) $record->commercial),
    'reach' => segment::count($criteria, false),
    'noconsentsource' => $record->commercial && !segment::commercial_policy() && !segment::exempt_cohort(),
    'sent' => $counts[campaign::SENT],
    'failed' => $counts[campaign::FAILED],
    'pending' => $counts[campaign::PENDING],
    'subject' => $subject,
    'preview' => $html,
    'editurl' => (new \core\url('/local/pspvmailing/manage/edit.php', ['id' => $id]))->out(false),
    'testurl' => $actionurl('test'),
    'testemail' => $USER->email,
    'sendnowurl' => $actionurl('sendnow'),
    'cancelurl' => $actionurl('cancel'),
    'deleteurl' => $actionurl('delete'),
    'listurl' => $list->out(false),
    'scheduleform' => $schedule ? $schedule->render() : '',
]);
echo $OUTPUT->footer();
