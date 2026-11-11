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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Tests of the segments, the consent, the sending of a campaign and the unsubscribe link.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(campaign::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(segment::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(sender::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(unsubscribe::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(task\send_batch::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(task\send_due_campaigns::class)]
final class mailing_test extends \advanced_testcase {
    /** @var int Id of the policy on commercial communications. */
    protected int $policyid;

    /** @var int Id of the current version of that policy. */
    protected int $versionid;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        // An optional, active policy on commercial communications.
        $version = \tool_policy\api::form_policydoc_add((object) [
            'name' => 'Commercial communications',
            'revision' => '1',
            'status' => \tool_policy\policy_version::STATUS_ACTIVE,
            'audience' => \tool_policy\policy_version::AUDIENCE_ALL,
            'type' => \tool_policy\policy_version::TYPE_OTHER,
            'optional' => 1,
            'agreementstyle' => \tool_policy\policy_version::AGREEMENTSTYLE_CONSENTPAGE,
            'summary_editor' => ['text' => 'Summary', 'format' => FORMAT_HTML, 'itemid' => 0],
            'content_editor' => ['text' => 'Content', 'format' => FORMAT_HTML, 'itemid' => 0],
        ]);
        \tool_policy\api::make_current($version->get('id'));
        $this->policyid = (int) $version->get('policyid');
        $this->versionid = (int) $version->get('id');
        set_config('commercialpolicy', $this->policyid, 'local_pspvmailing');
        // The tests say which cohort is the workers' one, whatever else is installed.
        set_config('workercohort', 'mailing-workers', 'local_pspvmailing');
        set_config('workersalways', 1, 'local_pspvmailing');
    }

    /**
     * Creates a user who has accepted, or not, the policy on commercial communications.
     *
     * @param bool $consent
     * @param array $record
     * @return \stdClass
     */
    protected function user(bool $consent, array $record = []): \stdClass {
        $user = $this->getDataGenerator()->create_user($record);
        if ($consent) {
            \tool_policy\api::accept_policies([$this->versionid], $user->id);
        }
        return $user;
    }

    /**
     * Creates the draft of a campaign.
     *
     * @param array $data
     * @return \stdClass
     */
    protected function campaign(array $data = []): \stdClass {
        return campaign::save((object) ($data + [
            'name' => 'Spring', 'subject' => 'Hello {{firstname}}', 'body' => '<p>Dear {{fullname}}, welcome to {{sitename}}.</p>',
            'commercial' => 1,
        ]));
    }

    /**
     * Runs the queued tasks that send campaigns.
     */
    protected function run_queue(): void {
        ob_start();
        $this->runAdhocTasks(task\send_batch::class);
        ob_end_clean();
    }

    public function test_commercial_campaigns_need_consent_except_for_exempted_workers(): void {
        $generator = $this->getDataGenerator();
        $workers = $generator->create_cohort(['idnumber' => 'mailing-workers']);
        $yes = $this->user(true);
        $no = $this->user(false);
        $worker = $this->user(false);
        cohort_add_member($workers->id, $worker->id);
        $out = $this->user(true);
        unsubscribe::opt_out((int) $out->id);
        $withdrawn = $this->user(true);
        \tool_policy\api::decline_policies([$this->versionid], $withdrawn->id);
        // Accounts that cannot or do not want to receive email are never written to.
        $this->user(true, ['suspended' => 1]);
        $this->user(true, ['emailstop' => 1]);
        $this->user(true, ['confirmed' => 0]);
        $admin = get_admin();

        $this->assertEqualsCanonicalizing([$yes->id, $worker->id], segment::user_ids([], true));
        $this->assertSame(2, segment::count([], true));
        // A service notice is not commercial: it reaches everyone in the segment.
        $everyone = [$admin->id, $yes->id, $no->id, $worker->id, $out->id, $withdrawn->id];
        $this->assertEqualsCanonicalizing($everyone, segment::user_ids([], false));

        // The exemption of the workers is a setting.
        set_config('workersalways', 0, 'local_pspvmailing');
        $this->assertEquals([$yes->id], segment::user_ids([], true));
        $this->assertSame(0, segment::exempt_cohort());

        // Without a policy to accept, nobody has given consent.
        set_config('commercialpolicy', 0, 'local_pspvmailing');
        $this->assertSame([], segment::user_ids([], true));
    }

    public function test_segments_narrow_by_cohort_and_course(): void {
        $generator = $this->getDataGenerator();
        $cohort = $generator->create_cohort();
        $course = $generator->create_course(['fullname' => 'Falls']);
        $both = $this->user(true);
        $onlycohort = $this->user(true);
        $onlycourse = $this->user(true);
        $this->user(true);
        cohort_add_member($cohort->id, $both->id);
        cohort_add_member($cohort->id, $onlycohort->id);
        $generator->enrol_user($both->id, $course->id);
        $generator->enrol_user($onlycourse->id, $course->id);
        $suspended = $this->user(true);
        $generator->enrol_user($suspended->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $this->assertEqualsCanonicalizing([$both->id, $onlycohort->id], segment::user_ids(['cohortid' => $cohort->id], true));
        $this->assertEqualsCanonicalizing([$both->id, $onlycourse->id], segment::user_ids(['courseid' => $course->id], true));
        $criteria = ['cohortid' => $cohort->id, 'courseid' => $course->id];
        $this->assertEquals([$both->id], segment::user_ids($criteria, true));
        $this->assertSame([], segment::user_ids(['courseid' => 999999], true));

        $this->assertCount(2, segment::describe($criteria));
        $this->assertStringContainsString('Falls', segment::describe($criteria)[1]);
        $this->assertSame([get_string('segment_everyone', 'local_pspvmailing')], segment::describe([]));
        // Unknown criteria are dropped.
        $clean = segment::normalise(['purchase' => 'x', 'other' => 1]);
        $this->assertSame(['cohortid' => 0, 'courseid' => 0, 'purchase' => ''], $clean);
    }

    public function test_a_scheduled_campaign_is_sent_in_batches_to_who_is_in_the_segment_then(): void {
        global $DB;

        $bea = $this->user(true, ['firstname' => 'Bea', 'lastname' => 'Buyer & Co', 'email' => 'bea@example.com']);
        $others = [$this->user(true), $this->user(true)];
        $campaign = $this->campaign();
        set_config('batchsize', 2, 'local_pspvmailing');
        $sink = $this->redirectEmails();
        $events = $this->redirectEvents();

        // Nothing happens before its time.
        campaign::schedule((int) $campaign->id, time() + HOURSECS);
        ob_start();
        (new task\send_due_campaigns())->execute();
        ob_end_clean();
        $this->assertSame(campaign::SCHEDULED, campaign::get((int) $campaign->id)->status);

        // Who gives consent later is included; who unsubscribes meanwhile is not.
        $late = $this->user(true);
        unsubscribe::opt_out((int) $others[1]->id);
        $DB->set_field('local_pspvmailing_campaign', 'timescheduled', time() - 1, ['id' => $campaign->id]);
        ob_start();
        (new task\send_due_campaigns())->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString('started for 3 recipients', $output);
        $this->assertSame(campaign::SENDING, campaign::get((int) $campaign->id)->status);
        $this->assertSame(0, $sink->count());

        // Each run of the queue sends a batch and queues the next one.
        $this->run_queue();
        $this->assertSame(2, $sink->count());
        $this->assertSame(campaign::SENDING, campaign::get((int) $campaign->id)->status);
        $this->run_queue();
        $this->assertSame(3, $sink->count());
        $finished = campaign::get((int) $campaign->id);
        $this->assertSame(campaign::SENT, $finished->status);
        $this->assertGreaterThan(0, $finished->timefinished);
        $counts = campaign::counts((int) $campaign->id);
        $this->assertSame([campaign::PENDING => 0, campaign::SENT => 3, campaign::FAILED => 0], $counts);

        $messages = array_column($sink->get_messages(), null, 'to');
        $this->assertEqualsCanonicalizing([$bea->email, $others[0]->email, $late->email], array_keys($messages));
        $message = $messages[$bea->email];
        $this->assertSame('Hello Bea', $message->subject);
        $body = quoted_printable_decode($message->body);
        $this->assertStringContainsString('Dear Bea Buyer &amp; Co, welcome to', $body);
        $this->assertStringContainsString('Dear Bea Buyer & Co, welcome to', $body);
        // The link to unsubscribe is in both versions of the body and in the header mail programs use.
        $link = unsubscribe::url((int) $bea->id)->out(false);
        $this->assertGreaterThanOrEqual(2, substr_count(str_replace('&amp;', '&', $body), $link));
        $this->assertStringContainsString('List-Unsubscribe: <' . $link . '>', $message->header);
        $this->assertStringContainsString('List-Unsubscribe-Post: List-Unsubscribe=One-Click', $message->header);

        $sent = array_values(array_filter($events->get_events(), fn($event) => $event instanceof event\campaign_sent));
        $this->assertCount(1, $sent);
        $this->assertSame(['sent' => 3, 'failed' => 0], $sent[0]->other);

        // A campaign that has been sent cannot be changed, sent again or deleted.
        try {
            campaign::save((object) ['id' => $campaign->id, 'name' => 'x', 'subject' => 'x', 'body' => 'x']);
            $this->fail('A sent campaign was changed.');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_notdraft', $e->errorcode);
        }
        $this->assertSame(0, campaign::start((int) $campaign->id));
        $this->expectException(\core\exception\moodle_exception::class);
        campaign::delete((int) $campaign->id);
    }

    public function test_campaigns_can_be_unscheduled_stopped_and_tested(): void {
        global $USER;

        $users = [$this->user(true), $this->user(true), $this->user(true)];
        set_config('batchsize', 1, 'local_pspvmailing');
        $sink = $this->redirectEmails();

        // A test goes to who asks for it, whatever their consent, and is marked as such.
        $campaign = $this->campaign(['commercial' => 0, 'subject' => 'Notice']);
        $this->assertTrue(sender::send($campaign, $USER, true));
        $test = $sink->get_messages()[0];
        $this->assertSame('[TEST] Notice', $test->subject);
        // A service notice has no unsubscribe link.
        $this->assertStringNotContainsString('unsubscribe.php', quoted_printable_decode($test->body));
        $this->assertStringNotContainsString('List-Unsubscribe', $test->header);
        $sink->clear();

        // A scheduled campaign goes back to draft and can be deleted.
        campaign::schedule((int) $campaign->id, time() + DAYSECS);
        campaign::cancel((int) $campaign->id);
        $this->assertSame(campaign::DRAFT, campaign::get((int) $campaign->id)->status);
        campaign::delete((int) $campaign->id);

        // A campaign being sent stops where it is.
        $campaign = $this->campaign();
        campaign::schedule((int) $campaign->id, time());
        $this->assertSame(3, campaign::start((int) $campaign->id));
        $this->run_queue();
        campaign::cancel((int) $campaign->id);
        $this->run_queue();
        $this->assertSame(1, $sink->count());
        $this->assertSame(campaign::CANCELLED, campaign::get((int) $campaign->id)->status);
        $this->assertSame(2, campaign::counts((int) $campaign->id)[campaign::PENDING]);

        // A campaign nobody can receive is finished at once.
        set_config('commercialpolicy', 0, 'local_pspvmailing');
        set_config('workersalways', 0, 'local_pspvmailing');
        $empty = $this->campaign();
        campaign::schedule((int) $empty->id, time());
        $this->assertSame(0, campaign::start((int) $empty->id));
        $this->assertSame(campaign::SENT, campaign::get((int) $empty->id)->status);
        $this->assertCount(3, $users);
    }

    public function test_the_unsubscribe_link_only_works_for_its_user(): void {
        $user = $this->user(true);
        $other = $this->user(true);
        $token = unsubscribe::token((int) $user->id);

        $this->assertTrue(unsubscribe::verify((int) $user->id, $token));
        $this->assertFalse(unsubscribe::verify((int) $other->id, $token));
        $this->assertFalse(unsubscribe::verify(0, $token));
        $this->assertFalse(unsubscribe::verify((int) $user->id, ''));
        $this->assertSame($token, unsubscribe::token((int) $user->id));
        $this->assertStringContainsString('t=' . $token, unsubscribe::url((int) $user->id)->out(false));

        $events = $this->redirectEvents();
        unsubscribe::opt_out((int) $user->id);
        unsubscribe::opt_out((int) $user->id);
        $this->assertTrue(unsubscribe::is_opted_out((int) $user->id));
        $this->assertFalse(unsubscribe::is_opted_out((int) $other->id));
        $this->assertCount(1, array_filter($events->get_events(), fn($event) => $event instanceof event\user_unsubscribed));
        $this->assertEquals([$other->id], segment::user_ids([], true));

        unsubscribe::opt_in((int) $user->id);
        $this->assertEqualsCanonicalizing([$user->id, $other->id], segment::user_ids([], true));
    }
}
