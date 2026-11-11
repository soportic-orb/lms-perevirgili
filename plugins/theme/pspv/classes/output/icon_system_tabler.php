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
 * Icon system that draws Moodle's icons with Tabler Icons.
 *
 * Moodle maps every icon (core and plugins) to a Font Awesome class. This system
 * takes that map and swaps each Font Awesome icon for its Tabler equivalent
 * (tabler_map.php). An icon without an equivalent, or one whose solid and outline
 * versions mean different things (a full and an empty star), keeps its Font
 * Awesome class, which Boost still loads.
 *
 * It deliberately stays a Font Awesome icon system for everything else: the
 * same template and the same JavaScript module render the icons, so nothing has
 * to be rebuilt.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon_system_tabler extends \core\output\icon_system_fontawesome {
    /** @var string[] Font Awesome style and family classes, dropped when an icon is swapped. */
    protected const FAMILIES = ['fa', 'fas', 'far', 'fa-solid', 'fa-regular', 'fa-fw'];

    /** @var string[] Font Awesome modifiers that are not icon names. */
    protected const MODIFIERS = ['fa-spin', 'fa-sm', 'fa-lg', 'fa-flip-horizontal', 'fa-rotate-90'];

    /** @var string[]|null Moodle icon => CSS classes, with Tabler icons where there is one. */
    protected ?array $tablermap = null;

    #[\Override]
    public function get_icon_name_map() {
        if ($this->tablermap === null) {
            $map = parent::get_icon_name_map();
            $ambiguous = $this->ambiguous($map);
            $tabler = require(__DIR__ . '/tabler_map.php');
            $this->tablermap = [];
            foreach ($map as $icon => $classes) {
                $this->tablermap[$icon] = $this->translate($classes, $tabler, $ambiguous);
            }
        }
        return $this->tablermap;
    }

    /**
     * Swaps the Font Awesome icon in a list of classes for its Tabler equivalent.
     *
     * @param string $classes Classes as Moodle maps them, such as "fa fa-pen" or "fa-regular fa-clock text-info".
     * @param string[] $tabler Font Awesome icon name => Tabler icon name.
     * @param string[] $ambiguous Font Awesome icon names that must not be swapped.
     * @return string
     */
    protected function translate(string $classes, array $tabler, array $ambiguous): string {
        $tokens = preg_split('/\s+/', trim($classes));
        foreach ($tokens as $index => $token) {
            if (!str_starts_with($token, 'fa-') || in_array($token, self::FAMILIES) || in_array($token, self::MODIFIERS)) {
                continue;
            }
            $name = substr($token, 3);
            if (!isset($tabler[$name]) || in_array($name, $ambiguous)) {
                return $classes;
            }
            $tokens[$index] = 'ti-' . $tabler[$name];
            $rest = array_diff($tokens, self::FAMILIES);
            return 'ti ' . implode(' ', $rest);
        }
        return $classes;
    }

    /**
     * Font Awesome icons used both solid and outlined, where the difference carries meaning.
     *
     * @param string[] $map Moodle icon => Font Awesome classes.
     * @return string[] Icon names without the "fa-" prefix.
     */
    protected function ambiguous(array $map): array {
        $styles = [];
        foreach ($map as $classes) {
            $tokens = preg_split('/\s+/', trim($classes));
            $style = in_array('fa-regular', $tokens) || in_array('far', $tokens) ? 'regular' : 'solid';
            foreach ($tokens as $token) {
                if (str_starts_with($token, 'fa-') && !in_array($token, self::FAMILIES) && !in_array($token, self::MODIFIERS)) {
                    $styles[substr($token, 3)][$style] = true;
                    break;
                }
            }
        }
        return array_keys(array_filter($styles, fn($found) => count($found) > 1));
    }
}
