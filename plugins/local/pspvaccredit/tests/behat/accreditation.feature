@local @local_pspvaccredit @pspv
Feature: Accreditation of a course
  In order to justify an accredited course
  As a teacher
  I need to record attendance and see which students passed

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Tina      | Teacher  | teacher@example.com |
      | student1 | Sam       | Student  | sam@example.com     |
      | student2 | Ann       | Absent   | ann@example.com     |
    And the following "courses" exist:
      | fullname   | shortname | customfield_pspv_accredited | customfield_pspv_filecode | customfield_pspv_credits | customfield_pspv_minattendance |
      | Wound care | WOUND     | 1                           | 09/0001-MD                | 2.4                      | 80                             |
      | Plain      | PLAIN     | 0                           |                           |                          |                                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | WOUND  | editingteacher |
      | student1 | WOUND  | student        |
      | student2 | WOUND  | student        |
      | teacher1 | PLAIN  | editingteacher |

  Scenario: Recording attendance makes the students who attended pass
    Given I am on the "WOUND" "Course" page logged in as "teacher1"
    And I navigate to "Accreditation" in current page administration
    And I should see "09/0001-MD"
    And I should see "80 %" in the "[data-region='pspvaccredit-course']" "css_element"
    And I should see "0 / 2" in the "[data-region='pspvaccredit-passed']" "css_element"
    When I follow "Add a session"
    And I set the following fields to these values:
      | Name               | Workshop |
      | Duration (minutes) | 120      |
    And I press "Save changes"
    Then I should see "Session saved."
    And I follow "Record attendance"
    And I set the field "Sam Student" to "1"
    And I press "Save attendance"
    And I should see "Attendance saved."
    And I should see "Passed" in the "Sam Student" "table_row"
    And I should see "100 %" in the "Sam Student" "table_row"
    And I should see "Pending" in the "Ann Absent" "table_row"
    And I should see "attendance" in the "Ann Absent" "table_row"
    And I should see "1 / 2" in the "[data-region='pspvaccredit-passed']" "css_element"

  Scenario: A teacher can record a student by hand and delete the record
    Given I am on the "WOUND" "Course" page logged in as "teacher1"
    And I navigate to "Accreditation" in current page administration
    When I click on "Mark as passed" "link" in the "Ann Absent" "table_row"
    Then I should see "The student has been recorded as passed."
    And I should see "by hand" in the "Ann Absent" "table_row"
    And I click on "Delete the record" "link" in the "Ann Absent" "table_row"
    And I should see "The record has been deleted."
    And I should see "Pending" in the "Ann Absent" "table_row"

  Scenario: A course that is not accredited says so
    Given I am on the "PLAIN" "Course" page logged in as "teacher1"
    When I navigate to "Accreditation" in current page administration
    Then I should see "This course is not marked as an accredited activity."
    And I should not see "Mark as passed"

  Scenario: Students cannot see the accreditation of a course
    Given I am on the "WOUND" "Course" page logged in as "student1"
    Then "Accreditation" "link" should not exist in current page administration
