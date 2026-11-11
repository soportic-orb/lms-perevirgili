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
 * Tests of the import of the employee list.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(employee_list::class)]
final class employee_list_test extends access_testcase {
    public function test_import_creates_updates_and_skips(): void {
        global $DB;

        $sink = $this->redirectEvents();
        $counts = employee_list::import_csv(
            "Email,EmployeeID,Department,JobCategory\n"
            . "Anna@PSPV.example,E1,Geriatrics,Nurse\n"
            . "pere@pspv.example,E2,,\n"
            . "not-an-email,E3,,\n"
            . "anna@pspv.example,E9,Duplicate,\n"
        );
        $this->assertSame(['created' => 2, 'updated' => 0, 'deleted' => 0, 'skipped' => 2], $counts);
        $this->assertSame(2, employee_list::count());
        $anna = $DB->get_record('local_pspvaccess_employee', ['email' => 'anna@pspv.example']);
        $this->assertSame(['E1', 'Geriatrics', 'Nurse'], [$anna->employeeid, $anna->department, $anna->jobcategory]);
        $this->assertNull($DB->get_field('local_pspvaccess_employee', 'department', ['email' => 'pere@pspv.example']));
        $events = array_filter($sink->get_events(), fn($event) => $event instanceof event\employee_list_imported);
        $this->assertCount(1, $events);

        // Semicolons, a change and a new row; without "replace" nobody is deleted.
        $counts = employee_list::import_csv("email;department\nanna@pspv.example;Rehabilitation\neva@pspv.example;\n", 'semicolon');
        $this->assertSame(['created' => 1, 'updated' => 1, 'deleted' => 0, 'skipped' => 0], $counts);
        $this->assertSame(3, employee_list::count());
        $anna = $DB->get_record('local_pspvaccess_employee', ['email' => 'anna@pspv.example']);
        $this->assertSame('Rehabilitation', $anna->department);
        // A column missing from the file clears the value: the file is the source of truth.
        $this->assertNull($anna->employeeid);
    }

    public function test_import_can_replace_the_whole_list(): void {
        global $DB;

        employee_list::import_csv("email\nanna@pspv.example\npere@pspv.example\n");
        $counts = employee_list::import_csv("email\npere@pspv.example\neva@pspv.example\n", 'comma', 'UTF-8', true);

        $this->assertSame(['created' => 1, 'updated' => 0, 'deleted' => 1, 'skipped' => 0], $counts);
        $this->assertEqualsCanonicalizing(
            ['pere@pspv.example', 'eva@pspv.example'],
            $DB->get_fieldset_select('local_pspvaccess_employee', 'email', '1 = 1'),
        );
    }

    public function test_import_needs_an_email_column(): void {
        try {
            employee_list::import_csv("name,department\nAnna,Geriatrics\n");
            $this->fail('A file without an email column was imported.');
        } catch (\moodle_exception $e) {
            $this->assertSame('employees_err_noemail', $e->errorcode);
        }
        $this->assertSame(0, employee_list::count());
    }
}
