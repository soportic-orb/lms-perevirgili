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

/**
 * Tests of the Tabler icon system.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(icon_system_tabler::class)]
final class icon_system_tabler_test extends \advanced_testcase {
    /**
     * The map of the icon system.
     *
     * @return string[]
     */
    protected function map(): array {
        $this->resetAfterTest();
        return \core\output\icon_system::instance(icon_system_tabler::class)->get_icon_name_map();
    }

    public function test_icons_with_an_equivalent_use_tabler(): void {
        $map = $this->map();
        $this->assertSame('ti ti-settings', $map['core:i/settings']);
        $this->assertSame('ti ti-pencil', $map['core:i/edit']);
        $this->assertSame('ti ti-trash', $map['core:i/delete']);
        $this->assertSame('ti ti-search', $map['core:a/search']);
    }

    public function test_colour_and_animation_classes_are_kept(): void {
        $map = $this->map();
        $this->assertSame('ti ti-alert-circle text-danger', $map['core:req']);
        $this->assertSame('ti ti-loader-2 fa-spin', $map['core:i/loading']);
        $this->assertSame('ti ti-help-circle text-info', $map['core:help']);
    }

    public function test_icons_whose_solid_and_outline_versions_differ_stay_in_font_awesome(): void {
        $map = $this->map();
        // A full star and an empty star mean different things.
        $this->assertStringContainsString('fa-star', $map['core:i/star']);
        $this->assertStringContainsString('fa-star', $map['core:i/star-o']);
        $this->assertStringNotContainsString('ti-', $map['core:i/star']);
    }

    public function test_every_icon_is_mapped_to_a_class_the_stylesheet_defines(): void {
        global $CFG;

        $scss = file_get_contents($CFG->dirroot . '/theme/pspv/scss/pspv/_icons.scss');
        $tabler = 0;
        foreach ($this->map() as $icon => $classes) {
            $this->assertNotSame('', trim($classes), "$icon has no class");
            if (preg_match('/\bti-([a-z0-9-]+)/', $classes, $match)) {
                $tabler++;
                $this->assertStringContainsString(".ti-{$match[1]}::before", $scss, "$icon uses an icon missing from _icons.scss");
                $this->assertStringNotContainsString('fa-solid', $classes);
                $this->assertStringNotContainsString('fa-regular', $classes);
            }
        }
        // Most of Moodle's icons are drawn with Tabler.
        $this->assertGreaterThan(300, $tabler);
    }

    public function test_the_icons_the_templates_use_are_in_the_stylesheet(): void {
        global $CFG;

        $scss = file_get_contents($CFG->dirroot . '/theme/pspv/scss/pspv/_icons.scss');
        foreach (glob($CFG->dirroot . '/theme/pspv/templates/{,*/}*.mustache', GLOB_BRACE) as $template) {
            preg_match_all('/class="ti ti-([a-z0-9-]+)/', file_get_contents($template), $matches);
            foreach ($matches[1] as $name) {
                $this->assertStringContainsString(".ti-{$name}::before", $scss, basename($template) . " uses ti-$name");
            }
        }
    }
}
