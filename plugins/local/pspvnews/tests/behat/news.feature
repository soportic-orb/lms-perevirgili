@local @local_pspvnews @pspv
Feature: News of the campus
  In order to tell people what is going on
  As a manager
  I need to write news that anyone can read from the top menu

  Background:
    Given the following config values are set as admin:
      | forcelogin | 0 |

  Scenario: A manager writes an article and a visitor reads it
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > Manage the news" in site administration
    And I should see "There are no news yet."
    When I follow "New article"
    And I set the following fields to these values:
      | Title   | Home care for older people           |
      | Summary | What families need to know to start. |
      | Text    | Caring at home takes organisation.   |
    And I press "Save changes"
    Then I should see "Article saved."
    And I should see "Published" in the "Home care for older people" "table_row"
    And I log out
    And I visit "/local/pspvnews/index.php"
    And I should see "What families need to know to start."
    And I follow "Home care for older people"
    And I should see "Caring at home takes organisation."
    And I follow "All the news"
    And I should see "Home care for older people"

  # With a real browser: the browser without JavaScript does not send an unticked checkbox the way people's do.
  @javascript
  Scenario: A draft is not shown to visitors
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > Manage the news" in site administration
    And I follow "New article"
    And I set the following fields to these values:
      | Title     | Not ready yet     |
      | Text      | Work in progress. |
      | Published | 0                 |
    And I press "Save changes"
    And I should see "Draft" in the "Not ready yet" "table_row"
    When I log out
    And I visit "/local/pspvnews/index.php"
    Then I should not see "Not ready yet"
    And I should see "There are no news yet."

  Scenario: An article can be deleted
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > Manage the news" in site administration
    And I follow "New article"
    And I set the following fields to these values:
      | Title | To be removed |
      | Text  | Short lived.  |
    And I press "Save changes"
    When I click on "Delete" "link" in the "To be removed" "table_row"
    And I press "Continue"
    Then I should see "Article deleted."
    And I should not see "To be removed"
