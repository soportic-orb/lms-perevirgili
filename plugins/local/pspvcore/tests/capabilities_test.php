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

namespace local_pspvcore;

/**
 * Tests of who can replace the site's code.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class capabilities_test extends \advanced_testcase {
    public function test_only_administrators_manage_updates(): void {
        global $DB;
        $this->resetAfterTest();

        $context = \core\context\system::instance();
        $manager = $this->getDataGenerator()->create_user();
        role_assign($DB->get_field('role', 'id', ['shortname' => 'manager']), $manager->id, $context->id);

        $this->assertFalse(has_capability('local/pspvcore:manageupdates', $context, $manager));
        $this->assertTrue(has_capability('local/pspvcore:manageupdates', $context, get_admin()));
    }

    public function test_check_task_does_nothing_without_a_public_key(): void {
        $this->resetAfterTest();
        $this->expectOutputRegex('/no public key configured/');
        (new task\check_updates())->execute();
        $this->assertSame(0, (new ota\updater())->last_check());
    }
}
