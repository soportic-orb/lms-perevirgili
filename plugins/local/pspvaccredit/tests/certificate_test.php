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

namespace local_pspvaccredit;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

/**
 * Tests of the diploma. They need mod_customcert, which is a community plugin: without it they are skipped.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(certificate::class)]
final class certificate_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        if (!certificate::available()) {
            $this->markTestSkipped('mod_customcert is not installed.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_site_template_is_built_once_with_the_accreditation_fields(): void {
        global $DB;

        $template = certificate::template();

        $this->assertEquals(\core\context\system::instance()->id, $template->get_contextid());
        $pages = $DB->get_records('customcert_pages', ['templateid' => $template->get_id()]);
        $this->assertCount(1, $pages);
        $page = reset($pages);
        $this->assertEquals([297, 210], [$page->width, $page->height]);
        $elements = $DB->get_records('customcert_elements', ['pageid' => $page->id], 'sequence');
        $types = array_column($elements, 'element');
        foreach (['border', 'studentname', 'coursename', 'date', 'code', 'qrcode'] as $type) {
            $this->assertContains($type, $types);
        }
        // Hours, credits and file code are printed from the custom fields of the course.
        $this->assertCount(3, array_keys($types, 'coursefield'));
        $coursefields = array_filter($elements, fn($element) => $element->element === 'coursefield');
        $handler = \core_course\customfield\course_handler::create();
        foreach ($coursefields as $element) {
            $fieldid = json_decode($element->data, true)['coursefield'];
            $this->assertStringStartsWith('pspv_', $handler->get_fields()[$fieldid]->get('shortname'));
        }
        // Diplomas can be verified by anyone.
        $this->assertEquals(1, get_config('customcert', 'verifyallcertificates'));

        // It is built once: after that it belongs to the administrators.
        $this->assertSame($template->get_id(), certificate::template()->get_id());
        $this->assertSame(1, $DB->count_records('customcert_templates'));
    }

    public function test_diploma_is_added_once_and_only_who_passed_can_open_it(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course([
            'customfield_pspv_accredited' => 1, 'customfield_pspv_mingrade' => 50, 'customfield_pspv_hours' => 20,
        ]);
        $passed = $generator->create_and_enrol($course, 'student');
        $pending = $generator->create_and_enrol($course, 'student');
        $item = $generator->create_grade_item(['courseid' => $course->id, 'itemtype' => 'manual', 'grademax' => 100]);
        \grade_item::fetch(['id' => $item->id])->update_final_grade($passed->id, 80);
        $this->assertNull(certificate::for_course((int) $course->id));

        $cm = certificate::add_to_course($course);

        $this->assertSame('customcert', $cm->modname);
        $this->assertSame(certificate::IDNUMBER, $cm->idnumber);
        $this->assertEquals($cm->id, certificate::for_course((int) $course->id)->id);
        $this->assertEquals($cm->id, certificate::add_to_course($course)->id);
        $this->assertCount(1, get_fast_modinfo($course->id)->get_instances_of('customcert'));

        // The activity has its own copy of the site template.
        $instance = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
        $this->assertNotEquals(certificate::template()->get_id(), $instance->templateid);
        $pages = $DB->get_records('customcert_pages', ['templateid' => $instance->templateid]);
        $this->assertCount(1, $pages);
        $this->assertGreaterThan(10, $DB->count_records('customcert_elements', ['pageid' => reset($pages)->id]));
        $this->assertEquals(1, $instance->verifyany);

        accreditation::sync_course((int) $course->id);
        $this->assertTrue(get_fast_modinfo($course->id, $passed->id)->get_cm($cm->id)->uservisible);
        $this->assertFalse(get_fast_modinfo($course->id, $pending->id)->get_cm($cm->id)->uservisible);

        // The diploma of who passed is a PDF.
        $this->setUser($passed);
        \mod_customcert\service\certificate_issue_service::create()->issue_certificate((int) $instance->id, (int) $passed->id);
        $template = \mod_customcert\template::from_record(
            $DB->get_record('customcert_templates', ['id' => $instance->templateid], '*', MUST_EXIST),
        );
        $pdf = \mod_customcert\service\pdf_generation_service::create()->generate_pdf($template, false, (int) $passed->id, true);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
