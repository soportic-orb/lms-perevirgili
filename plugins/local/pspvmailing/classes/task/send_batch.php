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
 * Sends a campaign to a batch of its recipients, and queues itself again while there are more.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_batch extends \core\task\adhoc_task {
    /** @var int Messages per run when the setting is empty. */
    public const DEFAULT_BATCH = 100;

    /**
     * Queues the sending of a campaign.
     *
     * @param int $campaignid
     * @param bool $unique Whether to skip it when the same sending is already queued. A task that queues
     *                     its own continuation cannot ask for that: it is still in the queue itself.
     */
    public static function queue(int $campaignid, bool $unique = true): void {
        $task = new self();
        $task->set_custom_data(['campaignid' => $campaignid]);
        $task->set_component('local_pspvmailing');
        \core\task\manager::queue_adhoc_task($task, $unique);
    }

    #[\Override]
    public function execute(): void {
        $campaignid = (int) $this->get_custom_data()->campaignid;
        $limit = (int) get_config('local_pspvmailing', 'batchsize');
        if (campaign::send_batch($campaignid, $limit > 0 ? $limit : self::DEFAULT_BATCH)) {
            self::queue($campaignid, false);
        }
    }
}
