@theme @theme_pspv @pspv
Feature: Public front page of the campus
  In order to find training without having an account
  As a visitor
  I need a front page with the catalogue and a clear way in for workers and for external users

  # The site settings below are the ones theme/pspv/cli/setup.php applies after a new installation.
  Background:
    Given the following config values are set as admin:
      | forcelogin        | 0             |            |
      | enablemyhome      | 1             |            |
      | defaulthomepage   | 0             |            |
      | frontpage         |               |            |
      | frontpageloggedin |               |            |
      | cataloguecategory | theme-catalog | theme_pspv |
    And the following "categories" exist:
      | name            | category | idnumber      |
      | Open catalogue  | 0        | theme-catalog |
    And the following "courses" exist:
      | fullname                 | shortname | category      |
      | Chronic wound care       | EXT1      | theme-catalog |
      | Falls prevention         | EXT2      | theme-catalog |
      | Course outside catalogue | OUT1      | 0             |

  Scenario: A visitor sees the entry points and the catalogue
    When I am on site homepage
    Then I should see "Training to care better for older people"
    And I should see "I am a worker"
    And I should see "I am a citizen"
    And I should see "Why train with Pere Virgili" in the "[data-region='pspv-why']" "css_element"
    And I should see "Online and at your own pace"
    And "Developed by Octavi Rodríguez" "link" should exist in the ".pspv-footer" "css_element"
    And ".btn-footer-popover.d-none" "css_element" should exist
    And I should see "Course catalogue"
    And I should see "Chronic wound care"
    And I should see "Falls prevention"
    And I should not see "Course outside catalogue"
    And "nav.navbar img[alt='Parc Sanitari Pere Virgili']" "css_element" should exist
    And I should see "Parc Sanitari Pere Virgili" in the ".pspv-footer" "css_element"

  Scenario: A visitor reaches the login page from the entry points
    Given I am on site homepage
    When I click on "[data-region='pspv-door-worker']" "css_element"
    Then I should see "Log in"
    And I should see "Training to care better for older people" in the ".login-layout-left" "css_element"

  Scenario: A logged-in user gets a shortcut to the dashboard instead of the entry points
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | student1 | Sam       | Student  | sam@example.com   |
    And I log in as "student1"
    When I am on site homepage
    Then I should see "Go to my dashboard"
    And I should not see "I am a worker"
    And I should see "Chronic wound care"

  Scenario: The top menu leads to the page about who runs the campus
    Given I am on site homepage
    When I follow "About us"
    Then I should see "Training team" in the "[data-region='pspv-about']" "css_element"
    And I should see "Sample text"
