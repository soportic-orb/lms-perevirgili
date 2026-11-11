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
 * Decides whether a student meets the criteria to pass an accredited course.
 *
 * The criteria are those the course has: its completion, when completion tracking has criteria,
 * a minimum grade and a minimum attendance. A course with no criterion at all passes nobody.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluator {
    /** @var \stdClass The course. */
    protected \stdClass $course;

    /** @var course_settings Its accreditation settings. */
    protected course_settings $settings;

    /** @var \completion_info Completion of the course. */
    protected \completion_info $completion;

    /** @var bool Whether completing the course is a criterion. */
    protected bool $usescompletion;

    /** @var float[]|null Attendance by user id. */
    protected ?array $attendance = null;

    /** @var bool Whether the course has sessions. */
    protected bool $hassessions = false;

    /**
     * Constructor.
     *
     * @param \stdClass $course
     * @param course_settings|null $settings
     */
    public function __construct(\stdClass $course, ?course_settings $settings = null) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');

        $this->course = $course;
        $this->settings = $settings ?? course_settings::for_course((int) $course->id);
        $this->completion = new \completion_info($course);
        $this->usescompletion = $this->completion->is_enabled() && $this->completion->has_criteria();
    }

    /**
     * The settings the evaluation uses.
     *
     * @return course_settings
     */
    public function settings(): course_settings {
        return $this->settings;
    }

    /**
     * Whether the course has any criterion to pass.
     *
     * @return bool
     */
    public function has_criteria(): bool {
        return $this->usescompletion || $this->settings->has_own_criteria();
    }

    /**
     * Evaluates a student.
     *
     * @param int $userid
     * @return \stdClass attendance (percentage or null when the course has no sessions), grade (percentage or null),
     *                   completed (bool, or null when completion is not a criterion), passed (bool) and
     *                   pending (string[] of: nocriteria, completion, grade, attendance).
     */
    public function evaluate(int $userid): \stdClass {
        if ($this->attendance === null) {
            [$this->attendance, $this->hassessions] = attendance::percentages((int) $this->course->id);
            // Grades changed since the last calculation of the course total are only read right after it is redone.
            grade_regrade_final_grades_if_required($this->course);
        }
        $result = (object) [
            'attendance' => $this->hassessions ? (float) ($this->attendance[$userid] ?? 0.0) : null,
            'grade' => $this->grade($userid),
            'completed' => $this->usescompletion ? $this->completion->is_course_complete($userid) : null,
            'pending' => [],
        ];
        if (!$this->has_criteria()) {
            $result->pending[] = 'nocriteria';
        }
        if ($result->completed === false) {
            $result->pending[] = 'completion';
        }
        if ($this->settings->mingrade !== null && ($result->grade === null || $result->grade < $this->settings->mingrade)) {
            $result->pending[] = 'grade';
        }
        $minattendance = $this->settings->minattendance;
        if ($minattendance !== null && ($result->attendance === null || $result->attendance < $minattendance)) {
            $result->pending[] = 'attendance';
        }
        $result->passed = empty($result->pending);
        return $result;
    }

    /**
     * Course grade of a student as a percentage.
     *
     * @param int $userid
     * @return float|null Null when there is no grade.
     */
    protected function grade(int $userid): ?float {
        $grade = grade_get_course_grade($userid, $this->course->id);
        if (!$grade || $grade->grade === null || $grade->grade === false || empty($grade->item)) {
            return null;
        }
        $min = (float) $grade->item->grademin;
        $max = (float) $grade->item->grademax;
        if ($max <= $min) {
            return null;
        }
        return round(((float) $grade->grade - $min) * 100 / ($max - $min), 2);
    }
}
