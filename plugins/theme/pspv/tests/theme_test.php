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

namespace theme_pspv;

/**
 * Tests of the theme as a whole: it compiles, and it carries the configured colours.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class theme_test extends \advanced_testcase {
    public function test_scss_compiles_with_the_default_colours(): void {
        $this->resetAfterTest();

        $css = \core\output\theme_config::load('pspv')->get_css_content();

        // Ours and Boost's: a broken SCSS file would leave only Boost's precompiled fallback.
        $this->assertStringContainsString('.pspv-hero', $css);
        $this->assertStringContainsString('.pspv-footer', $css);
        $this->assertStringContainsString('--pspv-primary:#0074d1', $css);
        $this->assertStringContainsString('"Plus Jakarta Sans"', $css);
        $this->assertStringContainsString('.ti-settings::before', $css);
        $this->assertStringNotContainsString('[[font:', $css);
    }

    public function test_colours_come_from_the_settings_and_text_stays_readable(): void {
        $this->resetAfterTest();
        set_config('brandcolor', '#ffcc00', 'theme_pspv');
        set_config('secondarycolor', 'not a colour', 'theme_pspv');

        $pre = theme_pspv_get_pre_scss(\core\output\theme_config::load('pspv'));

        $this->assertStringContainsString('$primary: #ffcc00;', $pre);
        // An invalid value falls back to the default instead of breaking the stylesheet.
        $this->assertStringContainsString('$pspv-secondary: #009de4;', $pre);
        $this->assertSame(1, preg_match('/\$pspv-primary-text: (#[0-9a-f]{6});/', $pre, $match));
        $this->assertGreaterThanOrEqual(4.5, local\colour::contrast($match[1], '#eef3f8'));
    }

    public function test_site_setup_runs_once_and_then_respects_the_administrator(): void {
        global $CFG;
        $this->resetAfterTest();
        set_config('enablemyhome', 0);
        set_config('defaulthomepage', HOMEPAGE_MY);

        $this->assertTrue(local\site_setup::apply_once());
        $this->assertSame('pspv', $CFG->theme);
        $this->assertEquals(1, $CFG->enablemyhome);
        $this->assertEquals(HOMEPAGE_SITE, $CFG->defaulthomepage);

        // An administrator changes the start page later: running the setup again keeps it.
        set_config('defaulthomepage', HOMEPAGE_MY);
        $this->assertFalse(local\site_setup::apply_once());
        $this->assertEquals(HOMEPAGE_MY, $CFG->defaulthomepage);
    }

    public function test_the_theme_is_a_child_of_boost_with_tabler_icons(): void {
        $theme = \core\output\theme_config::load('pspv');
        $this->assertSame(['boost'], $theme->parents);
        $this->assertSame(output\icon_system_tabler::class, $theme->get_icon_system());
    }
}
