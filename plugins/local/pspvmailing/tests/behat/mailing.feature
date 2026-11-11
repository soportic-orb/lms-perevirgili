@local @local_pspvmailing @pspv
Feature: Campaigns to segments of the users
  In order to tell users about courses
  As a manager
  I need to write a campaign, see who gets it and send it, and users need a way out

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | bea      | Bea       | Buyer    | bea@example.com |
      | sam      | Sam       | Student  | sam@example.com |
    And the following "cohorts" exist:
      | name    | idnumber |
      | Alumni  | alumni   |
    And the following "cohort members" exist:
      | user | cohort |
      | bea  | alumni |

  @javascript
  Scenario: A manager writes a service notice for a cohort and sends it
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > Campus PSPV mailing > Campaigns" in site administration
    And I should see "There are no campaigns yet."
    When I follow "New campaign"
    And I set the following fields to these values:
      | Name of the campaign     | Alumni notice              |
      | Subject                  | News for {{firstname}}     |
      | Message                  | Dear {{fullname}}, welcome |
      | Commercial communication | 0                          |
      | Members of the cohort    | Alumni                     |
    And I press "Save changes"
    Then I should see "Campaign saved."
    And I should see "Draft" in the "[data-region='pspvmailing-status']" "css_element"
    And I should see "Members of the cohort \"Alumni\""
    And I should see "It would be sent to 1 users today."
    And I should see "News for Admin"
    And I should see "Dear Admin User, welcome" in the "[data-region='pspvmailing-preview']" "css_element"
    And I follow "Send now"
    And I should see "The campaign will start being sent in a few minutes."
    And I should see "Scheduled" in the "[data-region='pspvmailing-status']" "css_element"
    And I run the scheduled task "\local_pspvmailing\task\send_due_campaigns"
    And I run all adhoc tasks
    And I reload the page
    And I should see "Sent" in the "[data-region='pspvmailing-status']" "css_element"
    And I should see "1" in the "[data-region='pspvmailing-sent']" "css_element"

  Scenario: A commercial campaign only counts who has given consent
    Given the following config values are set as admin:
      | commercialpolicy | 0 | local_pspvmailing |
      | workersalways    | 0 | local_pspvmailing |
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > Campus PSPV mailing > Campaigns" in site administration
    When I follow "New campaign"
    And I set the following fields to these values:
      | Name of the campaign | Spring courses |
      | Subject              | New courses    |
      | Message              | Have a look    |
    And I press "Save changes"
    Then I should see "It would be sent to 0 users today."
    And I should see "a commercial campaign reaches nobody"

  Scenario: A user unsubscribes from the link of an email, without logging in
    When I open the unsubscribe link of "bea"
    Then I should see "Do you want to stop receiving commercial communications from the campus?"
    And I press "Unsubscribe me"
    And I should see "You will not receive any more commercial communications from the campus."
    And I press "Subscribe me again"
    And I should see "Do you want to stop receiving commercial communications from the campus?"

  Scenario: An unsubscribe link that was tampered with does not work
    When I visit "/local/pspvmailing/unsubscribe.php?u=2&t=abc"
    Then I should see "This unsubscribe link is not valid."
