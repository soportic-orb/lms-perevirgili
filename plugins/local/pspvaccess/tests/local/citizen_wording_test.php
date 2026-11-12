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

namespace local_pspvaccess\local;

/**
 * Tests of the renaming of "external" to "citizens" in what the plugin had already created.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(structure::class)]
final class citizen_wording_test extends \advanced_testcase {
    public function test_default_names_are_renamed_and_changed_ones_are_kept(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $DB->get_record('course_categories', ['idnumber' => structure::CATEGORY_EXTERNAL], '*', MUST_EXIST);
        $role = $DB->get_record('role', ['shortname' => structure::ROLE_SALES_MANAGER], '*', MUST_EXIST);

        // A site installed before the change, in Catalan.
        $DB->update_record('course_categories', (object) [
            'id' => $category->id, 'name' => 'Formació externa',
            'description' => 'Cursos oberts a professionals externs i al públic general.',
        ]);
        $olddescription = 'Gestiona les vendes, els descomptes i les factures de la formació externa.';
        $DB->set_field('role', 'description', $olddescription, ['id' => $role->id]);

        $this->assertEqualsCanonicalizing(['category', 'description', 'role'], structure::apply_citizen_wording());

        $renamed = $DB->get_record('course_categories', ['id' => $category->id]);
        $this->assertSame(get_string('category_external', 'local_pspvaccess'), $renamed->name);
        $this->assertSame(get_string('category_external_desc', 'local_pspvaccess'), $renamed->description);
        $this->assertStringNotContainsStringIgnoringCase('extern', $renamed->name . $renamed->description);
        $this->assertSame(
            get_string('role_pspvsalesmanager_desc', 'local_pspvaccess'),
            $DB->get_field('role', 'description', ['id' => $role->id]),
        );
        // The idnumber, which is what the code looks for, does not change.
        $this->assertSame(structure::CATEGORY_EXTERNAL, $renamed->idnumber);
        // Nothing left to do the second time.
        $this->assertSame([], structure::apply_citizen_wording());

        // Names chosen by an administrator are not touched.
        $DB->update_record('course_categories', (object) [
            'id' => $category->id, 'name' => 'Cursos oberts', 'description' => 'Text propi',
        ]);
        $DB->set_field('role', 'description', 'Descripció pròpia', ['id' => $role->id]);
        $this->assertSame([], structure::apply_citizen_wording());
        $this->assertSame('Cursos oberts', $DB->get_field('course_categories', 'name', ['id' => $category->id]));
        $this->assertSame('Descripció pròpia', $DB->get_field('role', 'description', ['id' => $role->id]));
    }
}
