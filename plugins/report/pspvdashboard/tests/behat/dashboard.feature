@report @report_pspvdashboard @pspv
Feature: Dashboard of the campus
  In order to follow the activity of the campus
  As a manager
  I need to see enrolments and completions by course and period

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | student1 | Sam       | Student  | sam@example.com   |
      | student2 | Ann       | Other    | ann@example.com   |
      | manager1 | Mia       | Manager  | mia@example.com   |
    And the following "courses" exist:
      | fullname   | shortname |
      | Wound care | WOUND     |
      | Falls      | FALLS     |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | WOUND  | student |
      | student2 | WOUND  | student |
      | student1 | FALLS  | student |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: A manager sees the figures of each course and can limit them to a period
    Given I log in as "manager1"
    When I navigate to "Reports > Campus PSPV dashboard" in site administration
    Then I should see "3" in the "[data-region='pspvdashboard-enrolments']" "css_element"
    And I should see "2" in the "Wound care" "table_row"
    And I should see "1" in the "Falls" "table_row"
    And I set the field "From" to "2099-01-01"
    And I press "Filter"
    And I should see "0" in the "[data-region='pspvdashboard-enrolments']" "css_element"
    And I should see "There is nothing in this period."
    And I follow "Whole history"
    And I should see "Wound care"
