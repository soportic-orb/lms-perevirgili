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

namespace local_pspvmailing\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_pspvmailing\campaign;
use local_pspvmailing\unsubscribe;

/**
 * Tests of the privacy provider.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \advanced_testcase {
    public function test_received_campaigns_and_the_opt_out_are_exported_and_deleted(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $none = $this->getDataGenerator()->create_user();
        $campaign = campaign::save((object) [
            'name' => 'Notice', 'subject' => 'A notice', 'body' => '<p>Hi</p>', 'commercial' => 0,
        ]);
        foreach ([$user, $other] as $recipient) {
            $DB->insert_record('local_pspvmailing_recipient', [
                'campaignid' => $campaign->id, 'userid' => $recipient->id, 'status' => campaign::SENT, 'timesent' => time(),
            ]);
            unsubscribe::opt_out((int) $recipient->id);
        }
        $system = \core\context\system::instance();

        $this->assertEquals([$system->id], provider::get_contexts_for_userid((int) $user->id)->get_contextids());
        $this->assertSame([], provider::get_contexts_for_userid((int) $none->id)->get_contextids());
        $userlist = new \core_privacy\local\request\userlist($system, 'local_pspvmailing');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$user->id, $other->id], $userlist->get_userids());

        $approved = new approved_contextlist($user, 'local_pspvmailing', [$system->id]);
        provider::export_user_data($approved);
        $data = writer::with_context($system)->get_data([get_string('pluginname', 'local_pspvmailing')]);
        $this->assertSame('A notice', $data->campaigns[0]->subject);
        $this->assertNotEmpty($data->optedout);

        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('local_pspvmailing_recipient', ['userid' => $user->id]));
        $this->assertFalse(unsubscribe::is_opted_out((int) $user->id));
        $this->assertTrue($DB->record_exists('local_pspvmailing_recipient', ['userid' => $other->id]));
        $this->assertTrue(unsubscribe::is_opted_out((int) $other->id));

        $approvedusers = new \core_privacy\local\request\approved_userlist($system, 'local_pspvmailing', [$other->id]);
        provider::delete_data_for_users($approvedusers);
        $this->assertSame(0, $DB->count_records('local_pspvmailing_recipient'));
        $this->assertSame(0, $DB->count_records('local_pspvmailing_optout'));
        // The campaign itself is not personal data of its recipients.
        $this->assertSame(1, $DB->count_records('local_pspvmailing_campaign'));
    }
}
