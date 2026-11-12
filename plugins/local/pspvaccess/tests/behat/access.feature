@local @local_pspvaccess @pspv
Feature: Internal training is only for the workers of the hospital
  In order to keep the internal training for the staff
  As the hospital
  I need workers to see the internal training category and everybody else not to

  Background:
    Given the following config values are set as admin:
      | emaildomains | pspv.example | local_pspvaccess |
    And the following "users" exist:
      | username  | firstname | lastname | email              |
      | worker1   | Wanda     | Worker   | wanda@pspv.example |
      | external1 | Eric      | External | eric@example.com   |
    And the following "courses" exist:
      | fullname          | shortname | category      |
      | Hand hygiene      | INT1      | pspv-internal |
      | Geriatrics update | EXT1      | pspv-external |

  Scenario: A worker sees the internal and the external training
    Given I log in as "worker1"
    When I am on course index
    Then I should see "Internal training"
    And I should see "Training for citizens"
    And I follow "Internal training"
    And I should see "Hand hygiene"

  Scenario: An external user only sees the external training
    Given I log in as "external1"
    When I am on course index
    Then I should not see "Internal training"
    And I should not see "Hand hygiene"
    And I follow "Training for citizens"
    And I should see "Geriatrics update"

  Scenario: A visitor who has not logged in only sees the external training
    Given the following config values are set as admin:
      | forcelogin | 0 |
    When I am on course index
    Then I should not see "Internal training"
    And I should not see "Hand hygiene"
    And I follow "Training for citizens"
    And I should see "Geriatrics update"

  @javascript @_file_upload
  Scenario: An administrator imports the employee list and the audit log shows who became a worker
    Given the following config values are set as admin:
      | emaildomains    |   | local_pspvaccess |
      | useemployeelist | 1 | local_pspvaccess |
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > Campus PSPV access > Employee list" in site administration
    And I should see "0" in the "[data-region='employee-count']" "css_element"
    When I upload "local/pspvaccess/tests/fixtures/employees.csv" file to "File" filemanager
    And I press "Import the list"
    Then I should see "List imported: 2 new, 0 updated, 0 deleted, 1 rows skipped. Workers cohort: 1 added, 1 removed."
    And I should see "2" in the "[data-region='employee-count']" "css_element"
    And I navigate to "Plugins > Local plugins > Campus PSPV access > Workers cohort audit log" in site administration
    And I should see "Eric External"
    And I should see "Employee list" in the "Eric External" "table_row"
    And I should see "Manual re-synchronisation" in the "Eric External" "table_row"
