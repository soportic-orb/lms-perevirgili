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

namespace local_pspvaccess;

use local_pspvaccess\tests\access_testcase;

/**
 * Tests of the rules that decide who is a worker.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(worker_policy::class)]
final class worker_policy_test extends access_testcase {
    public function test_nobody_is_a_worker_until_a_rule_is_configured(): void {
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@pspv.example']);
        $this->assertSame([], worker_policy::enabled_rules());
        $this->assertFalse(worker_policy::is_worker($user));
    }

    public function test_domain_rule(): void {
        set_config('emaildomains', "PSPV.example\n @hospital.example ; other.example", 'local_pspvaccess');
        $this->assertSame(['pspv.example', 'hospital.example', 'other.example'], worker_policy::domains());
        $this->assertSame([worker_policy::RULE_DOMAIN], worker_policy::enabled_rules());

        $generator = $this->getDataGenerator();
        $this->assertTrue(worker_policy::is_worker($generator->create_user(['email' => 'Anna@PSPV.example'])));
        $this->assertTrue(worker_policy::is_worker($generator->create_user(['email' => 'pere@hospital.example'])));
        // Neither a subdomain nor a domain that merely ends the same way.
        $this->assertFalse(worker_policy::is_worker($generator->create_user(['email' => 'eva@mail.pspv.example'])));
        $this->assertFalse(worker_policy::is_worker($generator->create_user(['email' => 'eva@notpspv.example'])));
        $this->assertFalse(worker_policy::is_worker($generator->create_user(['email' => 'pspv.example@gmail.com'])));
    }

    public function test_accounts_that_cannot_be_trusted_are_never_workers(): void {
        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        $generator = $this->getDataGenerator();

        // The address has not been confirmed: anybody could have typed it.
        $this->assertFalse(worker_policy::is_worker($generator->create_user(['email' => 'a@pspv.example', 'confirmed' => 0])));
        $this->assertFalse(worker_policy::is_worker($generator->create_user(['email' => 'b@pspv.example', 'suspended' => 1])));
        $this->assertFalse(worker_policy::is_worker(guest_user()));
    }

    public function test_list_rule(): void {
        global $DB;

        set_config('useemployeelist', 1, 'local_pspvaccess');
        $DB->insert_record('local_pspvaccess_employee', ['email' => 'anna@gmail.com', 'timecreated' => 1, 'timemodified' => 1]);
        $generator = $this->getDataGenerator();

        $this->assertSame([worker_policy::RULE_LIST], worker_policy::enabled_rules());
        $this->assertTrue(worker_policy::is_worker($generator->create_user(['email' => 'Anna@Gmail.com'])));
        $this->assertFalse(worker_policy::is_worker($generator->create_user(['email' => 'pere@gmail.com'])));
    }

    public function test_tenant_rule(): void {
        $this->setAdminUser();
        $issuer = $this->create_worker_issuer();
        $generator = $this->getDataGenerator();
        $linked = $generator->create_user();
        $pending = $generator->create_user();
        $other = $generator->create_user();
        $this->link_login($linked->id, $issuer);
        $this->link_login($pending->id, $issuer, false);

        $this->assertSame([worker_policy::RULE_TENANT], worker_policy::enabled_rules());
        $this->assertTrue(worker_policy::is_worker($linked));
        $this->assertFalse(worker_policy::is_worker($pending));
        $this->assertFalse(worker_policy::is_worker($other));
    }

    public function test_tenant_rule_expires_without_recent_corporate_logins(): void {
        $this->setAdminUser();
        $issuer = $this->create_worker_issuer();
        set_config('tenantmaxage', 30, 'local_pspvaccess');
        $user = $this->getDataGenerator()->create_user();
        $this->link_login($user->id, $issuer);

        // Linked, but never seen signing in through the tenant.
        $this->assertFalse(worker_policy::is_worker($user));

        worker_manager::record_tenant_login($user->id);
        $this->assertTrue(worker_policy::is_worker($user));

        set_user_preference(worker_policy::PREF_TENANT_LOGIN, time() - 31 * DAYSECS, $user->id);
        $this->assertFalse(worker_policy::is_worker($user));
    }

    public function test_rules_can_be_required_together(): void {
        global $DB;

        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        set_config('useemployeelist', 1, 'local_pspvaccess');
        $DB->insert_record('local_pspvaccess_employee', ['email' => 'anna@pspv.example', 'timecreated' => 1, 'timemodified' => 1]);
        $generator = $this->getDataGenerator();
        $both = $generator->create_user(['email' => 'anna@pspv.example']);
        $domainonly = $generator->create_user(['email' => 'pere@pspv.example']);

        $this->assertTrue(worker_policy::is_worker($both));
        $this->assertTrue(worker_policy::is_worker($domainonly));
        $this->assertSame([worker_policy::RULE_DOMAIN, worker_policy::RULE_LIST], worker_policy::matching_rules($both));

        set_config('rulemode', 'all', 'local_pspvaccess');
        $this->assertTrue(worker_policy::is_worker($both));
        $this->assertFalse(worker_policy::is_worker($domainonly));
    }
}
