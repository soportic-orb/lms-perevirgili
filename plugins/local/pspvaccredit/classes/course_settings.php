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

/**
 * The accreditation data and the passing criteria of a course, read from its custom fields.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_settings {
    /** @var bool Whether the course is an accredited activity. */
    public bool $accredited = false;

    /** @var string File code given by the accrediting body. */
    public string $filecode = '';

    /** @var float|null Credits. */
    public ?float $credits = null;

    /** @var float|null Hours. */
    public ?float $hours = null;

    /** @var int Start of the accreditation, 0 when not set. */
    public int $accreditedfrom = 0;

    /** @var int End of the accreditation, 0 when not set. */
    public int $accrediteduntil = 0;

    /** @var float|null Minimum attendance to pass, as a percentage. */
    public ?float $minattendance = null;

    /** @var float|null Minimum course grade to pass, as a percentage. */
    public ?float $mingrade = null;

    /**
     * Reads the settings of a course.
     *
     * @param int $courseid
     * @return self
     */
    public static function for_course(int $courseid): self {
        $settings = new self();
        $handler = \core_course\customfield\course_handler::create();
        foreach ($handler->get_instance_data($courseid, true) as $data) {
            $shortname = $data->get_field()->get('shortname');
            $value = $data->get_value();
            switch ($shortname) {
                case 'pspv_accredited':
                    $settings->accredited = !empty($value);
                    break;
                case 'pspv_filecode':
                    $settings->filecode = trim((string) $value);
                    break;
                case 'pspv_accreditedfrom':
                case 'pspv_accrediteduntil':
                    $settings->{substr($shortname, 5)} = (int) $value;
                    break;
                case 'pspv_credits':
                case 'pspv_hours':
                case 'pspv_minattendance':
                case 'pspv_mingrade':
                    $settings->{substr($shortname, 5)} = ($value === null || $value === '') ? null : (float) $value;
                    break;
            }
        }
        return $settings;
    }

    /**
     * Ids of the courses marked as accredited.
     *
     * @return int[]
     */
    public static function accredited_course_ids(): array {
        global $DB;

        $sql = "SELECT d.instanceid
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                  JOIN {customfield_category} c ON c.id = f.categoryid
                  JOIN {course} co ON co.id = d.instanceid
                 WHERE c.component = :component AND c.area = :area AND f.shortname = :shortname AND d.intvalue = 1";
        $params = ['component' => 'core_course', 'area' => 'course', 'shortname' => 'pspv_accredited'];
        return array_map('intval', $DB->get_fieldset_sql($sql, $params));
    }

    /**
     * Whether a criterion decides who passes.
     *
     * @return bool
     */
    public function has_own_criteria(): bool {
        return $this->minattendance !== null || $this->mingrade !== null;
    }
}
