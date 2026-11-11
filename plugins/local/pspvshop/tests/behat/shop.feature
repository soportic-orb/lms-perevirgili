@local @local_pspvshop @pspv
Feature: Buying a course in the campus shop
  In order to take a paid course
  As an external user
  I need to find it in the public catalogue, put it in the cart and be enrolled when I pay

  Background:
    Given the following config values are set as admin:
      | forcelogin | 0             |                |
      | category   | shop-catalog  | local_pspvshop |
      | testmode   | 1             | local_pspvshop |
      # The billing step of local_pspvinvoice, when installed, has its own feature.
      | enabled    | 0             | local_pspvinvoice |
    And the following "categories" exist:
      | name     | category | idnumber     |
      | Nursing  | 0        | shop-catalog |
    And the following "courses" exist:
      | fullname           | shortname | category     | summary                  |
      | Chronic wound care | WOUND     | shop-catalog | Pressure injuries course |
      | Falls prevention   | FALLS     | shop-catalog | Avoiding falls           |
    And the following "local_pspvshop > products" exist:
      | course | price | mode   | seats |
      | WOUND  | 75    | online | 12    |
      | FALLS  | 40    | onsite |       |
    And the following "local_pspvshop > coupons" exist:
      | code      | discounttype | amount |
      | WELCOME10 | percent      | 10     |
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | buyer1   | Bea       | Buyer    | bea@example.com |

  Scenario: A visitor browses the catalogue and filters it
    When I visit "/local/pspvshop/index.php"
    Then I should see "Chronic wound care"
    And I should see "Falls prevention"
    And I should see "75.00"
    And I should see "Places left: 12"
    And I set the field "Mode" to "On site"
    And I press "Filter"
    And I should see "Falls prevention"
    And I should not see "Chronic wound care"
    And I should see "Courses found: 1"

  Scenario: A visitor fills the cart, logs in and finds it waiting
    Given I visit "/local/pspvshop/index.php"
    And I follow "Chronic wound care"
    And I should see "Pressure injuries course"
    When I press "Add to cart"
    Then I should see "The course is in your cart."
    And I should see "75.00" in the "[data-region='cart-total']" "css_element"
    And I set the field "Discount code" to "welcome10"
    And I press "Apply"
    And I should see "67.50" in the "[data-region='cart-total']" "css_element"
    And I log in as "buyer1"
    And I visit "/local/pspvshop/cart.php"
    And I should see "Chronic wound care"
    And I should see "67.50" in the "[data-region='cart-total']" "css_element"

  Scenario: An administrator completes a test purchase and is enrolled
    Given I log in as "admin"
    And I visit "/local/pspvshop/index.php"
    And I follow "Falls prevention"
    And I press "Add to cart"
    When I press "Continue to payment"
    Then I should see "40.00" in the "[data-region='order-total']" "css_element"
    And I should see "Online payment is not set up yet."
    And I press "Simulate the payment"
    And I should see "Payment received."
    And I should see "Paid" in the "[data-region='order-status']" "css_element"
    And I follow "Falls prevention"
    And I should see "Falls prevention" in the "page-header" "region"
    And I navigate to "Plugins > Local plugins > Campus PSPV shop > Orders" in site administration
    And I should see "Admin User"
    And I should see "pspvtest"

  Scenario: A buyer without permission cannot simulate payments
    Given I log in as "buyer1"
    And I visit "/local/pspvshop/index.php"
    And I follow "Chronic wound care"
    And I press "Add to cart"
    When I press "Continue to payment"
    Then I should see "Online payment is not set up yet."
    And "Simulate the payment" "button" should not exist
    And I visit "/local/pspvshop/orders.php"
    And I should see "Pending payment"

  Scenario: A manager puts a course on sale
    Given the following "courses" exist:
      | fullname      | shortname | category     |
      | New course    | NEW       | shop-catalog |
    And I log in as "admin"
    And I navigate to "Plugins > Local plugins > Campus PSPV shop > Courses on sale" in site administration
    And I should see "Not on sale" in the "New course" "table_row"
    When I click on "Put on sale" "link" in the "New course" "table_row"
    And I set the following fields to these values:
      | General price (EUR) | 99.5 |
      | Mode                | Blended |
    And I press "Save changes"
    Then I should see "On sale" in the "New course" "table_row"
    And I should see "99.50" in the "New course" "table_row"
    And I visit "/local/pspvshop/index.php"
    And I should see "New course"
