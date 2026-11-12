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

namespace report_pspvdashboard;

/**
 * Tests of the figures of the dashboard.
 *
 * @package    report_pspvdashboard
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(stats::class)]
final class stats_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Marks a course as completed by a user.
     *
     * @param \stdClass $course
     * @param \stdClass $user
     * @param int $time
     */
    protected function complete(\stdClass $course, \stdClass $user, int $time): void {
        global $DB;

        $DB->insert_record('course_completions', [
            'userid' => $user->id, 'course' => $course->id, 'timeenrolled' => $time, 'timestarted' => $time,
            'timecompleted' => $time, 'reaggregate' => 0,
        ]);
    }

    public function test_summary_and_courses_count_what_happened_in_the_period(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $busy = $generator->create_course(['fullname' => 'Busy']);
        $quiet = $generator->create_course(['fullname' => 'Quiet']);
        $generator->create_course(['fullname' => 'Empty']);
        $users = [$generator->create_user(), $generator->create_user(), $generator->create_user()];
        $now = time();
        foreach ($users as $user) {
            $generator->enrol_user($user->id, $busy->id);
        }
        $generator->enrol_user($users[0]->id, $quiet->id);
        // One enrolment is from last year.
        $old = $now - YEARSECS;
        $instance = $DB->get_record('enrol', ['courseid' => $quiet->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $DB->set_field('user_enrolments', 'timecreated', $old, ['enrolid' => $instance->id]);
        $this->complete($busy, $users[0], $now);
        $this->complete($busy, $users[1], $now);
        $this->complete($quiet, $users[0], $old);
        // Deleted users are not counted.
        delete_user($users[2]);

        $all = new stats();
        $summary = $all->summary();
        $this->assertSame([3, 3], [$summary['enrolments'], $summary['completions']]);
        $courses = $all->by_course();
        $this->assertSame(['Busy', 'Quiet'], array_column($courses, 'fullname'));
        $this->assertSame([2, 2], [$courses[$busy->id]['enrolments'], $courses[$busy->id]['completions']]);
        $this->assertSame([1, 1], [$courses[$quiet->id]['enrolments'], $courses[$quiet->id]['completions']]);

        $recent = new stats($now - DAYSECS, $now + DAYSECS);
        $summary = $recent->summary();
        $this->assertSame([2, 2], [$summary['enrolments'], $summary['completions']]);
        $this->assertSame(['Busy'], array_column($recent->by_course(), 'fullname'));

        $past = new stats(0, $now - WEEKSECS);
        $this->assertSame(['Quiet'], array_column($past->by_course(), 'fullname'));
        $this->assertSame([], (new stats($now + DAYSECS))->by_course());
    }

    public function test_sales_and_revenue_come_from_the_paid_orders_of_the_shop(): void {
        global $DB;

        if (!stats::has_sales()) {
            $this->assertArrayNotHasKey('orders', (new stats())->summary());
            $this->markTestSkipped('local_pspvshop is not installed.');
        }
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Wound care']);
        $other = $generator->create_course(['fullname' => 'Falls']);
        $user = $generator->create_user();
        $now = time();
        $order = function (string $status, int $timepaid, array $items) use ($DB, $user, $now): void {
            $total = array_sum($items);
            $orderid = $DB->insert_record('local_pspvshop_order', [
                'userid' => $user->id, 'status' => $status, 'currency' => 'EUR', 'subtotal' => $total, 'discount' => 0,
                'tax' => 0, 'total' => $total, 'timecreated' => $now, 'timepaid' => $timepaid, 'timemodified' => $now,
            ]);
            foreach ($items as $courseid => $amount) {
                $DB->insert_record('local_pspvshop_orderitem', [
                    'orderid' => $orderid, 'productid' => 0, 'courseid' => $courseid, 'name' => 'Course',
                    'unitprice' => $amount, 'discount' => 0, 'taxrate' => 0, 'tax' => 0, 'total' => $amount,
                ]);
            }
        };
        $order('paid', $now, [$course->id => 75.5, $other->id => 40]);
        $order('paid', $now - YEARSECS, [$course->id => 60]);
        // Only what was paid and not refunded is revenue.
        $order('pending', 0, [$course->id => 75.5]);
        $order('refunded', $now, [$course->id => 75.5]);
        $order('cancelled', 0, [$other->id => 40]);

        $summary = (new stats())->summary();
        $this->assertSame(2, $summary['orders']);
        $this->assertSame(175.5, $summary['revenue']);
        $this->assertSame('EUR', $summary['currency']);
        $courses = (new stats())->by_course();
        $this->assertSame([2, 135.5], [$courses[$course->id]['sold'], $courses[$course->id]['revenue']]);
        $this->assertSame([1, 40.0], [$courses[$other->id]['sold'], $courses[$other->id]['revenue']]);

        $recent = new stats($now - DAYSECS);
        $this->assertSame([1, 115.5], [$recent->summary()['orders'], $recent->summary()['revenue']]);
        $this->assertSame(75.5, $recent->by_course()[$course->id]['revenue']);
        $this->assertStringContainsString(format_float(115.5, 2), stats::money(115.5, 'EUR'));
    }

    public function test_internal_training_is_broken_down_by_service_and_job_category(): void {
        global $DB;

        $this->assertSame([], (new stats())->internal_by('email'));
        if (!stats::has_staff_list()) {
            $this->assertSame([], (new stats())->internal_by('department'));
            $this->markTestSkipped('local_pspvaccess is not installed.');
        }
        $generator = $this->getDataGenerator();
        $internal = $DB->get_record('course_categories', ['idnumber' => stats::INTERNAL_CATEGORY])
            ?: $generator->create_category(['idnumber' => stats::INTERNAL_CATEGORY]);
        $sub = $generator->create_category(['parent' => $internal->id]);
        $first = $generator->create_course(['category' => $internal->id]);
        $second = $generator->create_course(['category' => $sub->id]);
        $external = $generator->create_course();
        $now = time();
        $staff = [
            'ana@example.com' => ['Geriatrics', 'Nurse'],
            'bru@example.com' => ['Geriatrics', 'Physician'],
            'cai@example.com' => ['Rehabilitation', 'Nurse'],
            'dan@example.com' => [null, 'Nurse'],
        ];
        $users = [];
        foreach ($staff as $email => [$department, $jobcategory]) {
            $DB->insert_record('local_pspvaccess_employee', [
                'email' => $email, 'department' => $department, 'jobcategory' => $jobcategory,
                'timecreated' => $now, 'timemodified' => $now,
            ]);
            // Email addresses match whatever their case.
            $users[$email] = $generator->create_user(['email' => ucfirst($email)]);
            $generator->enrol_user($users[$email]->id, $first->id);
        }
        $generator->enrol_user($users['ana@example.com']->id, $second->id);
        // Training outside the internal category, and people who are not on the staff list, do not count.
        $generator->enrol_user($users['ana@example.com']->id, $external->id);
        $generator->enrol_user($generator->create_user()->id, $first->id);
        $this->complete($first, $users['ana@example.com'], $now);
        $this->complete($second, $users['ana@example.com'], $now);
        $this->complete($first, $users['cai@example.com'], $now);
        $this->complete($external, $users['bru@example.com'], $now);

        $departments = array_column((new stats())->internal_by('department'), null, 'name');
        $this->assertSame(['Geriatrics', 'Rehabilitation', ''], array_keys($departments));
        $this->assertSame(
            ['name' => 'Geriatrics', 'people' => 2, 'enrolments' => 3, 'completions' => 2],
            $departments['Geriatrics'],
        );
        $this->assertSame([1, 1, 1], [
            $departments['Rehabilitation']['people'], $departments['Rehabilitation']['enrolments'],
            $departments['Rehabilitation']['completions'],
        ]);
        $this->assertSame(0, $departments['']['completions']);

        $jobs = array_column((new stats())->internal_by('jobcategory'), null, 'name');
        $this->assertSame([3, 4, 3], [$jobs['Nurse']['people'], $jobs['Nurse']['enrolments'], $jobs['Nurse']['completions']]);
        $this->assertSame([1, 1, 0], [
            $jobs['Physician']['people'], $jobs['Physician']['enrolments'], $jobs['Physician']['completions'],
        ]);

        $this->assertSame([], (new stats($now + DAYSECS))->internal_by('department'));
    }
}
