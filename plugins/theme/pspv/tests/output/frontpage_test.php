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

namespace theme_pspv\output;

use theme_pspv\local\catalogue;

/**
 * Tests of the public front page and its catalogue.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(frontpage::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(catalogue::class)]
final class frontpage_test extends \advanced_testcase {
    /**
     * Data of the front page for the current user.
     *
     * @return array
     */
    protected function export(): array {
        global $PAGE;

        $PAGE->set_url('/');
        $PAGE->set_context(\core\context\system::instance());
        return (new frontpage())->export_for_template($PAGE->get_renderer('core'));
    }

    /**
     * The catalogue category, named "External". The access plugin creates it when it is installed.
     *
     * @return \core_course_category
     */
    protected function external_category(): \core_course_category {
        global $DB;

        $id = $DB->get_field('course_categories', 'id', ['idnumber' => catalogue::DEFAULT_CATEGORY]);
        if (!$id) {
            $id = $this->getDataGenerator()->create_category(['idnumber' => catalogue::DEFAULT_CATEGORY])->id;
        }
        $category = \core_course_category::get($id, MUST_EXIST, true);
        $category->update(['name' => 'External']);
        return \core_course_category::get($id, MUST_EXIST, true);
    }

    public function test_catalogue_lists_the_visible_courses_of_the_external_category(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $external = $this->external_category();
        $sub = $generator->create_category(['parent' => $external->id, 'name' => 'Geriatrics']);
        $other = $generator->create_category(['name' => 'Internal']);
        $generator->create_course([
            'category' => $external->id, 'fullname' => 'Wound care', 'summary' => '<p>Chronic <b>wounds</b>.</p>',
            'startdate' => time() + WEEKSECS,
        ]);
        $generator->create_course(['category' => $sub->id, 'fullname' => 'Falls', 'startdate' => time() - WEEKSECS]);
        $generator->create_course(['category' => $external->id, 'fullname' => 'Hidden', 'visible' => 0]);
        $generator->create_course(['category' => $other->id, 'fullname' => 'Hand hygiene']);
        // The catalogue is the theme's own unless a shop plugin offers one.
        set_config('category', '', 'local_pspvshop');
        $this->setGuestUser();

        $data = $this->export();

        $this->assertTrue($data['hascourses']);
        $byname = array_column($data['courses'], null, 'name');
        $this->assertEqualsCanonicalizing(['Wound care', 'Falls'], array_keys($byname));
        $this->assertSame('Chronic wounds.', $byname['Wound care']['summary']);
        $this->assertSame('External', $byname['Wound care']['category']);
        $this->assertSame('Geriatrics', $byname['Falls']['category']);
        $this->assertTrue($byname['Wound care']['hasstart']);
        // A course that has already started shows no start date.
        $this->assertFalse($byname['Falls']['hasstart']);
        $this->assertFalse($byname['Falls']['hasimage']);
        $this->assertStringContainsString('categoryid=' . $external->id, $data['catalogueurl']);

        set_config('cataloguecount', 1, 'theme_pspv');
        $this->assertCount(1, $this->export()['courses']);
    }

    public function test_front_page_without_a_catalogue_category(): void {
        $this->resetAfterTest();
        set_config('cataloguecategory', 'no-such-category', 'theme_pspv');
        $this->getDataGenerator()->create_course(['fullname' => 'Somewhere else']);

        $data = $this->export();

        $this->assertFalse($data['hascourses']);
        $this->assertSame([], $data['courses']);
        $this->assertSame(get_string('herotitle_default', 'theme_pspv'), $data['title']);
    }

    public function test_entry_points_are_only_for_visitors(): void {
        $this->resetAfterTest();

        $data = $this->export();
        $this->assertTrue($data['showdoors']);
        // Without a configured corporate sign-in provider, workers go to the login page.
        $this->assertStringEndsWith('/login/index.php', $data['workerurl']);

        $this->setGuestUser();
        $this->assertTrue($this->export()['showdoors']);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse($this->export()['showdoors']);
    }

    public function test_texts_can_be_replaced_from_the_settings(): void {
        $this->resetAfterTest();
        set_config('herotitle', 'Welcome to the <b>campus</b>', 'theme_pspv');

        $data = $this->export();

        $this->assertSame('Welcome to the campus', $data['title']);
        $this->assertSame(get_string('herolead_default', 'theme_pspv'), $data['lead']);
    }
}
