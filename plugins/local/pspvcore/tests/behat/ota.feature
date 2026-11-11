@local @local_pspvcore @pspv
Feature: Campus PSPV updates page
  In order to keep the Campus PSPV plugins up to date
  As an administrator
  I need a page that shows the update status and what stops updates

  Scenario: An administrator sees the status and that the public key is missing
    Given I log in as "admin"
    When I navigate to "Plugins > Local plugins > Campus PSPV updates" in site administration
    Then I should see "Current version"
    And I should see "As installed (no update applied)"
    And I should see "The public key that verifies the updates is not configured"
    And I should see "No newer version has been found."
    And "Check for updates" "button" should exist
    And I should not see "Apply version"
    And "Undo the last update" "button" should not exist
