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

/**
 * Strings for local_pspvaccredit, English.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accreditation'] = 'Accreditation';
$string['attendance'] = 'Attendance';
$string['attendance_legend'] = 'Tick who attended';
$string['attendance_mark'] = 'Record attendance';
$string['attendance_of'] = 'Attendance: {$a}';
$string['attendance_save'] = 'Save attendance';
$string['attendance_saved'] = 'Attendance saved.';
$string['award'] = 'Mark as passed';
$string['award_done'] = 'The student has been recorded as passed.';
$string['completed'] = 'Course completed';
$string['data'] = 'Accreditation data';
$string['diploma'] = 'Diploma';
$string['diploma_activity'] = 'Diploma';
$string['diploma_add'] = 'Add the diploma to this course';
$string['diploma_border'] = 'Border';
$string['diploma_certifies'] = 'certifies that';
$string['diploma_code'] = 'Verification code';
$string['diploma_completed'] = 'has successfully completed the training activity';
$string['diploma_course'] = 'Course';
$string['diploma_date'] = 'Date of issue';
$string['diploma_desc'] = 'Adds a certificate activity built from the site template. Only the students recorded as passed can open it, and it carries a code and a QR that anyone can verify.';
$string['diploma_exists'] = 'This course has its diploma.';
$string['diploma_logo'] = 'Logo';
$string['diploma_open'] = 'Open the diploma';
$string['diploma_owner'] = 'Institution';
$string['diploma_qr'] = 'Verification QR';
$string['diploma_student'] = 'Name of the student';
$string['diploma_template'] = 'PSPV diploma';
$string['diploma_title'] = 'Diploma';
$string['diploma_unavailable'] = 'Diplomas need the Custom certificate activity (mod_customcert), which is not installed on this site.';
$string['diploma_verify'] = 'This diploma can be verified at {$a}';
$string['editsettings'] = 'Edit them in the course settings';
$string['event_accreditation_awarded'] = 'Student passed an accredited course';
$string['event_accreditation_revoked'] = 'Accreditation record deleted';
$string['event_attendance_marked'] = 'Attendance recorded';
$string['export'] = 'Export who passed as';
$string['field_pspv_accredited'] = 'Accredited activity';
$string['field_pspv_accredited_desc'] = 'Tick it when the course is accredited. Students who meet the criteria are then recorded as passed.';
$string['field_pspv_accreditedfrom'] = 'Accredited from';
$string['field_pspv_accreditedfrom_desc'] = 'First day of the accreditation.';
$string['field_pspv_accrediteduntil'] = 'Accredited until';
$string['field_pspv_accrediteduntil_desc'] = 'Last day of the accreditation.';
$string['field_pspv_credits'] = 'Credits';
$string['field_pspv_credits_desc'] = 'Credits granted by the accrediting body.';
$string['field_pspv_filecode'] = 'File code';
$string['field_pspv_filecode_desc'] = 'Code of the accreditation file.';
$string['field_pspv_hours'] = 'Hours';
$string['field_pspv_hours_desc'] = 'Accredited teaching hours.';
$string['field_pspv_minattendance'] = 'Minimum attendance (%)';
$string['field_pspv_minattendance_desc'] = 'Share of the time of the sessions a student has to attend to pass. Leave it empty if attendance is not required.';
$string['field_pspv_mingrade'] = 'Minimum grade (%)';
$string['field_pspv_mingrade_desc'] = 'Course grade, as a percentage of the maximum, a student needs to pass. Leave it empty if no grade is required.';
$string['fieldcategory'] = 'Accreditation';
$string['filter_from'] = 'Passed from';
$string['filter_to'] = 'Passed until';
$string['groupdescription'] = 'Students recorded as passed. It is filled in by the accreditation plugin and gives access to the diploma.';
$string['groupname'] = 'Passed';
$string['manual'] = 'by hand';
$string['minattendance'] = 'Minimum attendance';
$string['mingrade'] = 'Minimum grade';
$string['nocriteria'] = 'This course has no criterion to pass, so nobody is recorded automatically. Set a minimum attendance or grade in the course settings, or turn on completion tracking with criteria.';
$string['notaccredited'] = 'This course is not marked as an accredited activity.';
$string['notpassed'] = 'Pending';
$string['notrequired'] = 'Not required';
$string['passed'] = 'Passed';
$string['pending_attendance'] = 'attendance';
$string['pending_completion'] = 'course completion';
$string['pending_grade'] = 'grade';
$string['pending_nocriteria'] = 'no criteria';
$string['period'] = 'Accreditation period';
$string['pluginname'] = 'Campus PSPV accreditation';
$string['present'] = 'Attended';
$string['privacy:metadata:attend'] = 'Who attended each session of a course.';
$string['privacy:metadata:attend:markedby'] = 'Who recorded the attendance.';
$string['privacy:metadata:attend:sessionid'] = 'The session.';
$string['privacy:metadata:attend:timemodified'] = 'When it was recorded.';
$string['privacy:metadata:attend:userid'] = 'The student who attended.';
$string['privacy:metadata:record'] = 'Register of the students who passed an accredited course.';
$string['privacy:metadata:record:attendance'] = 'Attendance of the student when recorded.';
$string['privacy:metadata:record:awardedby'] = 'Who recorded the student by hand, if anybody.';
$string['privacy:metadata:record:coursename'] = 'Name of the course.';
$string['privacy:metadata:record:credits'] = 'Credits of the course.';
$string['privacy:metadata:record:filecode'] = 'File code of the accreditation.';
$string['privacy:metadata:record:grade'] = 'Grade of the student when recorded.';
$string['privacy:metadata:record:hours'] = 'Hours of the course.';
$string['privacy:metadata:record:timeawarded'] = 'When the student was recorded as passed.';
$string['privacy:metadata:record:userid'] = 'The student.';
$string['pspvaccredit:manage'] = 'Manage the accreditation of a course';
$string['pspvaccredit:markattendance'] = 'Record attendance to sessions';
$string['pspvaccredit:viewreport'] = 'See and export who passed';
$string['records'] = 'Register of students who passed';
$string['records_count'] = '{$a} records';
$string['records_none'] = 'Nobody has passed an accredited course in this period.';
$string['report_attendance'] = 'Attendance (%)';
$string['report_credits'] = 'Credits';
$string['report_date'] = 'Passed on';
$string['report_document'] = 'Identity document';
$string['report_filecode'] = 'File code';
$string['report_grade'] = 'Grade (%)';
$string['report_hours'] = 'Hours';
$string['revoke'] = 'Delete the record';
$string['revoke_done'] = 'The record has been deleted.';
$string['session'] = 'Session';
$string['session_add'] = 'Add a session';
$string['session_date'] = 'Date and time';
$string['session_delete'] = 'Delete this session';
$string['session_delete_confirm'] = 'Delete the session "{$a}" and its attendance?';
$string['session_deleted'] = 'Session deleted.';
$string['session_duration'] = 'Duration (minutes)';
$string['session_duration_help'] = 'Attendance is counted over the time of all the sessions, so a long session weighs more than a short one. If no session has a duration, every session counts the same.';
$string['session_duration_invalid'] = 'The duration cannot be negative.';
$string['session_edit'] = 'Edit the session';
$string['session_name'] = 'Name';
$string['session_saved'] = 'Session saved.';
$string['sessions'] = 'Sessions';
$string['sessions_none'] = 'This course has no sessions with recorded attendance.';
$string['setting_idfield'] = 'Identity document of the students';
$string['setting_idfield_desc'] = 'Field of the user that holds the identity document (NIF/NIE) printed on the reports for the accrediting body. TODO: the hospital has to say where it is kept; until then the column stays empty.';
$string['setting_idfield_none'] = 'None';
$string['settings'] = 'Accreditation settings';
$string['students'] = 'Students';
$string['students_none'] = 'This course has no students.';
$string['sync'] = 'Recalculate who passed';
$string['sync_done'] = '{$a} students newly recorded as passed.';
$string['task_sync'] = 'Record the students who passed accredited courses';
