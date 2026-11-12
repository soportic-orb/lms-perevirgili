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
 * The "About us" page: who is behind the campus.
 *
 * Its text is written in the theme settings; while it is empty, a sample text is shown.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class about {
    /**
     * Whether the page exists and is linked from the top menu.
     *
     * @return bool
     */
    public static function enabled(): bool {
        $show = get_config('theme_pspv', 'showabout');
        return $show === false || !empty($show);
    }

    /**
     * URL of the page.
     *
     * @return \core\url
     */
    public static function url(): \core\url {
        return new \core\url('/theme/pspv/about.php');
    }

    /**
     * The text of the page.
     *
     * @return string HTML.
     */
    public static function content(): string {
        $text = trim((string) get_config('theme_pspv', 'abouttext'));
        if (trim(html_to_text($text, 0, false)) === '') {
            return get_string('about_default', 'theme_pspv');
        }
        return format_text($text, FORMAT_HTML, ['context' => \core\context\system::instance()]);
    }

    /**
     * Whether the page shows the sample text.
     *
     * @return bool
     */
    public static function is_sample(): bool {
        return trim(html_to_text((string) get_config('theme_pspv', 'abouttext'), 0, false)) === '';
    }
}
