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

namespace local_pspvmailing;

/**
 * Campaigns and their life: draft, scheduled, sending, sent or cancelled.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class campaign {
    /** @var string Being written. */
    public const DRAFT = 'draft';

    /** @var string Waiting for its time. */
    public const SCHEDULED = 'scheduled';

    /** @var string Being sent by the task queue. */
    public const SENDING = 'sending';

    /** @var string Sent to everyone. */
    public const SENT = 'sent';

    /** @var string Stopped before or while sending. */
    public const CANCELLED = 'cancelled';

    /** @var string A recipient the message has not been sent to yet. */
    public const PENDING = 'pending';

    /** @var string A recipient the message could not be sent to. */
    public const FAILED = 'failed';

    /**
     * A campaign.
     *
     * @param int $id
     * @return \stdClass
     */
    public static function get(int $id): \stdClass {
        global $DB;

        return $DB->get_record('local_pspvmailing_campaign', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * The segment of a campaign.
     *
     * @param \stdClass $campaign
     * @return array
     */
    public static function criteria(\stdClass $campaign): array {
        return segment::normalise(json_decode((string) $campaign->segment, true) ?: []);
    }

    /**
     * Creates or updates a draft. A campaign that is not a draft cannot be changed.
     *
     * @param \stdClass $data name, subject, body, commercial, cohortid, courseid, purchase and, to update, id.
     * @return \stdClass The campaign.
     */
    public static function save(\stdClass $data): \stdClass {
        global $DB, $USER;

        $now = time();
        $record = (object) [
            'name' => \core_text::substr(trim($data->name), 0, 255),
            'subject' => \core_text::substr(trim($data->subject), 0, 255),
            'body' => (string) $data->body,
            'commercial' => empty($data->commercial) ? 0 : 1,
            'segment' => json_encode(segment::normalise((array) $data)),
            'usermodified' => $USER->id,
            'timemodified' => $now,
        ];
        if (!empty($data->id)) {
            if (self::get((int) $data->id)->status !== self::DRAFT) {
                throw new \core\exception\moodle_exception('error_notdraft', 'local_pspvmailing');
            }
            $record->id = (int) $data->id;
            $DB->update_record('local_pspvmailing_campaign', $record);
        } else {
            $record->status = self::DRAFT;
            $record->timecreated = $now;
            $record->id = $DB->insert_record('local_pspvmailing_campaign', $record);
        }
        return self::get((int) $record->id);
    }

    /**
     * Deletes a campaign that has not been sent to anybody.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;

        if (!in_array(self::get($id)->status, [self::DRAFT, self::SCHEDULED], true)) {
            throw new \core\exception\moodle_exception('error_notdraft', 'local_pspvmailing');
        }
        $DB->delete_records('local_pspvmailing_recipient', ['campaignid' => $id]);
        $DB->delete_records('local_pspvmailing_campaign', ['id' => $id]);
    }

    /**
     * Leaves a draft ready to be sent at a given time.
     *
     * @param int $id
     * @param int $time When to send it; now or earlier sends it on the next run of the task.
     */
    public static function schedule(int $id, int $time): void {
        global $DB;

        if (self::get($id)->status !== self::DRAFT) {
            throw new \core\exception\moodle_exception('error_notdraft', 'local_pspvmailing');
        }
        $DB->update_record('local_pspvmailing_campaign', (object) [
            'id' => $id, 'status' => self::SCHEDULED, 'timescheduled' => $time, 'timemodified' => time(),
        ]);
    }

    /**
     * Stops a campaign: a scheduled one goes back to draft, one being sent is not sent to who is left.
     *
     * @param int $id
     */
    public static function cancel(int $id): void {
        global $DB;

        $campaign = self::get($id);
        if ($campaign->status === self::SCHEDULED) {
            $DB->update_record('local_pspvmailing_campaign', (object) [
                'id' => $id, 'status' => self::DRAFT, 'timescheduled' => 0, 'timemodified' => time(),
            ]);
        } else if ($campaign->status === self::SENDING) {
            $DB->update_record('local_pspvmailing_campaign', (object) [
                'id' => $id, 'status' => self::CANCELLED, 'timefinished' => time(), 'timemodified' => time(),
            ]);
        }
    }

    /**
     * Starts sending a scheduled campaign: fixes who receives it and queues the sending.
     *
     * The recipients are those of the segment at this moment, not when the campaign was written.
     *
     * @param int $id
     * @return int Number of recipients.
     */
    public static function start(int $id): int {
        global $DB;

        $campaign = self::get($id);
        if ($campaign->status !== self::SCHEDULED) {
            return 0;
        }
        $userids = segment::user_ids(self::criteria($campaign), (bool) $campaign->commercial);
        $rows = array_map(fn($userid) => ['campaignid' => $id, 'userid' => $userid, 'status' => self::PENDING], $userids);
        $DB->delete_records('local_pspvmailing_recipient', ['campaignid' => $id]);
        if ($rows) {
            $DB->insert_records('local_pspvmailing_recipient', $rows);
        }
        $DB->update_record('local_pspvmailing_campaign', (object) [
            'id' => $id, 'status' => self::SENDING, 'timestarted' => time(), 'timemodified' => time(),
        ]);
        if ($rows) {
            task\send_batch::queue($id);
        } else {
            self::finish($id);
        }
        return count($rows);
    }

    /**
     * Sends a campaign to the next recipients in the queue.
     *
     * @param int $id
     * @param int $limit Maximum number of messages.
     * @return bool Whether there are recipients left.
     */
    public static function send_batch(int $id, int $limit): bool {
        global $DB;

        $campaign = self::get($id);
        if ($campaign->status !== self::SENDING) {
            return false;
        }
        $conditions = ['campaignid' => $id, 'status' => self::PENDING];
        foreach ($DB->get_records('local_pspvmailing_recipient', $conditions, 'id', '*', 0, $limit) as $recipient) {
            $user = \core_user::get_user($recipient->userid);
            // Somebody may have opted out or left between the start of the campaign and their turn.
            $send = $user && !$user->deleted && !$user->suspended
                && !($campaign->commercial && unsubscribe::is_opted_out((int) $user->id));
            $sent = $send && sender::send($campaign, $user);
            $DB->update_record('local_pspvmailing_recipient', (object) [
                'id' => $recipient->id, 'status' => $sent ? self::SENT : self::FAILED, 'timesent' => $sent ? time() : 0,
            ]);
        }
        if ($DB->record_exists('local_pspvmailing_recipient', $conditions)) {
            return true;
        }
        self::finish($id);
        return false;
    }

    /**
     * Marks a campaign as sent.
     *
     * @param int $id
     */
    protected static function finish(int $id): void {
        global $DB;

        $DB->update_record('local_pspvmailing_campaign', (object) [
            'id' => $id, 'status' => self::SENT, 'timefinished' => time(), 'timemodified' => time(),
        ]);
        $counts = self::counts($id);
        event\campaign_sent::create([
            'context' => \core\context\system::instance(),
            'objectid' => $id,
            'other' => ['sent' => $counts[self::SENT], 'failed' => $counts[self::FAILED]],
        ])->trigger();
    }

    /**
     * How many recipients of a campaign are in each state.
     *
     * @param int $id
     * @return int[] By state: pending, sent and failed.
     */
    public static function counts(int $id): array {
        global $DB;

        $counts = [self::PENDING => 0, self::SENT => 0, self::FAILED => 0];
        $sql = "SELECT status, COUNT(1) AS total FROM {local_pspvmailing_recipient} WHERE campaignid = ? GROUP BY status";
        foreach ($DB->get_records_sql($sql, [$id]) as $row) {
            $counts[$row->status] = (int) $row->total;
        }
        return $counts;
    }
}
