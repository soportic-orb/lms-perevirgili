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
 * List of campaigns.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvmailing\campaign;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_pspvmailing_campaigns');
require_capability('local/pspvmailing:manage', \core\context\system::instance());

$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
$rows = [];
foreach ($DB->get_records('local_pspvmailing_campaign', null, 'timecreated DESC, id DESC') as $record) {
    $counts = campaign::counts((int) $record->id);
    $when = $record->timefinished ?: ($record->timestarted ?: $record->timescheduled);
    $rows[] = [
        'name' => format_string($record->name),
        'url' => (new \core\url('/local/pspvmailing/manage/view.php', ['id' => $record->id]))->out(false),
        'status' => get_string('status_' . $record->status, 'local_pspvmailing'),
        'isdraft' => $record->status === campaign::DRAFT,
        'issent' => $record->status === campaign::SENT,
        'commercial' => (bool) $record->commercial,
        'when' => $when ? userdate($when, $dateformat) : '',
        'sent' => $counts[campaign::SENT],
        'total' => array_sum($counts),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('campaigns', 'local_pspvmailing'));
echo $OUTPUT->render_from_template('local_pspvmailing/campaigns', [
    'addurl' => (new \core\url('/local/pspvmailing/manage/edit.php'))->out(false),
    'campaigns' => $rows,
    'hascampaigns' => !empty($rows),
]);
echo $OUTPUT->footer();
