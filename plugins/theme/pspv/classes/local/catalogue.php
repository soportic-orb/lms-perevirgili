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
 * The courses shown on the public front page.
 *
 * They are the courses of the external training category that the current user
 * can see, newest first. Prices, filters and credits arrive with the shop and
 * accreditation plugins.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue {
    /** @var string Idnumber of the external training category created by local_pspvaccess. */
    public const DEFAULT_CATEGORY = 'pspv-external';

    /** @var int Number of cover colours defined in the stylesheet. */
    protected const TONES = 6;

    /**
     * The category whose courses make the catalogue.
     *
     * @return \core_course_category|null Null when it does not exist or the user cannot see it.
     */
    public static function category(): ?\core_course_category {
        global $DB;

        // Until the settings page has been saved once there is no stored value.
        $idnumber = get_config('theme_pspv', 'cataloguecategory');
        $idnumber = $idnumber === false ? self::DEFAULT_CATEGORY : trim((string) $idnumber);
        if ($idnumber === '') {
            return null;
        }
        $id = $DB->get_field('course_categories', 'id', ['idnumber' => $idnumber]);
        return $id ? \core_course_category::get($id, IGNORE_MISSING) : null;
    }

    /**
     * URL of the whole catalogue.
     *
     * @return \core\url
     */
    public static function url(): \core\url {
        $category = self::category();
        return new \core\url('/course/index.php', $category ? ['categoryid' => $category->id] : []);
    }

    /**
     * The courses of the catalogue, ready for the front page template.
     *
     * @param int $limit Maximum number of courses.
     * @return array[]
     */
    public static function courses(int $limit): array {
        $category = self::category();
        if (!$category || $limit <= 0) {
            return [];
        }
        $courses = $category->get_courses([
            'recursive' => true,
            'limit' => $limit,
            'sort' => ['startdate' => -1, 'id' => -1],
            'summary' => true,
            'coursecontacts' => false,
        ]);

        $cards = [];
        foreach ($courses as $course) {
            $image = self::image($course);
            $coursecategory = \core_course_category::get($course->category, IGNORE_MISSING);
            $context = \core\context\course::instance($course->id);
            // Plain text for the card; the template escapes it again on output.
            $summary = html_entity_decode(
                strip_tags(format_text($course->summary, $course->summaryformat, ['context' => $context])),
                ENT_QUOTES | ENT_HTML5,
            );
            $cards[] = [
                'url' => (new \core\url('/course/view.php', ['id' => $course->id]))->out(false),
                'name' => $course->get_formatted_fullname(),
                'category' => $coursecategory ? $coursecategory->get_formatted_name() : '',
                'summary' => shorten_text(trim(preg_replace('/\s+/', ' ', $summary)), 140),
                'hasimage' => $image !== null,
                'image' => (string) $image,
                'tone' => $course->id % self::TONES + 1,
                'hasstart' => $course->startdate > time(),
                'start' => userdate($course->startdate, get_string('strftimedate', 'core_langconfig')),
            ];
        }
        return $cards;
    }

    /**
     * URL of the first image among the course overview files.
     *
     * @param \core_course_list_element $course
     * @return string|null
     */
    protected static function image(\core_course_list_element $course): ?string {
        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return \core\url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename(),
                )->out(false);
            }
        }
        return null;
    }
}
