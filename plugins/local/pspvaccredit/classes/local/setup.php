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

namespace local_pspvaccredit\local;

/**
 * Creates the course custom fields that hold the accreditation data of a course.
 *
 * They are ordinary course custom fields, so they are edited in the settings of the course,
 * travel with its backups and can be printed on a diploma. They are always looked up by shortname.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /** @var int Custom field visible to nobody but who can edit the course. */
    protected const VISIBLE_TEACHERS = 1;

    /** @var int Custom field visible to everyone. */
    protected const VISIBLE_ALL = 2;

    /**
     * The fields, by shortname: type, visibility and configuration of the type.
     *
     * @return array[]
     */
    public static function fields(): array {
        $percent = ['decimalplaces' => 0, 'minimumvalue' => 0, 'maximumvalue' => 100, 'display' => '{value} %'];
        $date = ['includetime' => 0, 'mindate' => 0, 'maxdate' => 0];
        return [
            'pspv_accredited' => ['checkbox', self::VISIBLE_ALL, ['checkbydefault' => 0]],
            'pspv_filecode' => ['text', self::VISIBLE_ALL, ['displaysize' => 30, 'maxlength' => 100]],
            'pspv_credits' => ['number', self::VISIBLE_ALL, ['decimalplaces' => 1, 'minimumvalue' => 0, 'display' => '{value}']],
            'pspv_hours' => ['number', self::VISIBLE_ALL, ['decimalplaces' => 1, 'minimumvalue' => 0, 'display' => '{value}']],
            'pspv_accreditedfrom' => ['date', self::VISIBLE_TEACHERS, $date],
            'pspv_accrediteduntil' => ['date', self::VISIBLE_TEACHERS, $date],
            'pspv_minattendance' => ['number', self::VISIBLE_TEACHERS, $percent],
            'pspv_mingrade' => ['number', self::VISIBLE_TEACHERS, $percent],
        ];
    }

    /**
     * Creates the fields that do not exist yet. Safe to run any number of times.
     *
     * @return string[] Shortnames of the fields created.
     */
    public static function ensure_fields(): array {
        $handler = \core_course\customfield\course_handler::create();
        $existing = [];
        $categoryid = 0;
        foreach ($handler->get_fields() as $field) {
            $existing[$field->get('shortname')] = true;
            if (str_starts_with($field->get('shortname'), 'pspv_')) {
                $categoryid = (int) $field->get('categoryid');
            }
        }
        $missing = array_diff_key(self::fields(), $existing);
        if (!$missing) {
            return [];
        }
        if (!$categoryid) {
            $categoryid = $handler->create_category(self::site_string('fieldcategory'));
        }
        $category = \core_customfield\category_controller::create($categoryid);

        foreach ($missing as $shortname => [$type, $visibility, $config]) {
            $field = \core_customfield\field_controller::create(0, (object) ['type' => $type], $category);
            $handler->save_field_configuration($field, (object) [
                'name' => self::site_string('field_' . $shortname),
                'shortname' => $shortname,
                'description' => self::site_string('field_' . $shortname . '_desc'),
                'descriptionformat' => FORMAT_HTML,
                'configdata' => $config + [
                    'required' => 0,
                    'uniquevalues' => 0,
                    'locked' => 0,
                    'visibility' => $visibility,
                    'defaultvalue' => '',
                    'defaultvalueformat' => FORMAT_MOODLE,
                ],
            ]);
        }
        return array_keys($missing);
    }

    /**
     * A string of the plugin in the default language of the site, which is the one stored data is written in.
     *
     * @param string $identifier
     * @param mixed $a
     * @return string
     */
    public static function site_string(string $identifier, $a = null): string {
        global $CFG;

        return get_string_manager()->get_string($identifier, 'local_pspvaccredit', $a, $CFG->lang ?? 'en');
    }
}
