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

namespace local_pspvmailing\task;

use local_pspvmailing\campaign;

/**
 * Starts the campaigns whose time has come.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_due_campaigns extends \core\task\scheduled_task {
    #[\Override]
    public function get_name(): string {
        return get_string('task_send_due', 'local_pspvmailing');
    }

    #[\Override]
    public function execute(): void {
        global $DB;

        $due = $DB->get_records_select(
            'local_pspvmailing_campaign',
            'status = :status AND timescheduled <= :now',
            ['status' => campaign::SCHEDULED, 'now' => time()],
            'timescheduled',
            'id, name',
        );
        foreach ($due as $campaign) {
            $recipients = campaign::start((int) $campaign->id);
            mtrace("Campaign {$campaign->id} started for $recipients recipients.");
        }
    }
}
