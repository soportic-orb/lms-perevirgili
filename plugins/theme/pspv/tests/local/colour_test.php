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

namespace theme_pspv\local;

/**
 * Tests of the colour helpers.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(colour::class)]
final class colour_test extends \basic_testcase {
    public function test_clean(): void {
        $this->assertSame('#0074d1', colour::clean(' #0074D1 ', '#000000'));
        $this->assertSame('#aabbcc', colour::clean('#abc', '#000000'));
        $this->assertSame('#000000', colour::clean('blue', '#000000'));
        $this->assertSame('#000000', colour::clean('#0074d1; } body { display: none', '#000000'));
        $this->assertSame('#000000', colour::clean('', '#000000'));
    }

    public function test_contrast(): void {
        $this->assertEqualsWithDelta(21.0, colour::contrast('#000000', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(1.0, colour::contrast('#0074d1', '#0074d1'), 0.01);
        // The blue of the hospital's logo passes WCAG AA on white, the light blue does not.
        $this->assertEqualsWithDelta(4.75, colour::contrast('#0074d1', '#ffffff'), 0.05);
        $this->assertLessThan(4.5, colour::contrast('#009de4', '#ffffff'));
    }

    public function test_readable_darkens_only_as_much_as_needed(): void {
        // Already readable: unchanged.
        $this->assertSame('#0074d1', colour::readable('#0074d1', '#ffffff'));

        // Not readable on a tinted background: a darker shade of the same hue that is.
        $text = colour::readable('#0074d1', '#eef3f8');
        $this->assertNotSame('#0074d1', $text);
        $this->assertGreaterThanOrEqual(4.5, colour::contrast($text, '#eef3f8'));
        $this->assertStringStartsWith('#00', $text);

        // Whatever colour an administrator picks, the result is readable.
        foreach (['#ffff00', '#ffffff', '#ff8800', '#00ff88', '#123456'] as $colour) {
            $this->assertGreaterThanOrEqual(4.5, colour::contrast(colour::readable($colour, '#eef3f8'), '#eef3f8'));
        }
    }
}
