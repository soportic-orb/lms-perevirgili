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
 * Colour helpers: validation and WCAG contrast.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class colour {
    /**
     * Returns a colour as #rrggbb, or the default when it is not a valid hexadecimal colour.
     *
     * @param string $colour
     * @param string $default
     * @return string
     */
    public static function clean(string $colour, string $default): string {
        $colour = strtolower(trim($colour));
        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $colour, $short)) {
            return '#' . $short[1] . $short[1] . $short[2] . $short[2] . $short[3] . $short[3];
        }
        return preg_match('/^#[0-9a-f]{6}$/', $colour) ? $colour : $default;
    }

    /**
     * WCAG 2.1 contrast ratio between two colours.
     *
     * @param string $a Colour as #rrggbb.
     * @param string $b Colour as #rrggbb.
     * @return float Between 1 and 21.
     */
    public static function contrast(string $a, string $b): float {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Darkens a colour, keeping its hue, until text in it is readable on a background.
     *
     * @param string $colour Colour as #rrggbb.
     * @param string $background Background as #rrggbb.
     * @param float $ratio Contrast ratio to reach (4.5 is WCAG AA for normal text).
     * @return string Colour as #rrggbb.
     */
    public static function readable(string $colour, string $background = '#ffffff', float $ratio = 4.5): string {
        [$r, $g, $b] = self::rgb($colour);
        for ($step = 0; $step <= 20; $step++) {
            $factor = 1 - $step * 0.05;
            $candidate = sprintf('#%02x%02x%02x', round($r * $factor), round($g * $factor), round($b * $factor));
            if (self::contrast($candidate, $background) >= $ratio) {
                return $candidate;
            }
        }
        return '#000000';
    }

    /**
     * Relative luminance of a colour.
     *
     * @param string $colour Colour as #rrggbb.
     * @return float Between 0 and 1.
     */
    protected static function luminance(string $colour): float {
        $channels = array_map(function (int $value): float {
            $value /= 255;
            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, self::rgb($colour));
        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Red, green and blue of a colour.
     *
     * @param string $colour Colour as #rrggbb.
     * @return int[]
     */
    protected static function rgb(string $colour): array {
        return [hexdec(substr($colour, 1, 2)), hexdec(substr($colour, 3, 2)), hexdec(substr($colour, 5, 2))];
    }
}
