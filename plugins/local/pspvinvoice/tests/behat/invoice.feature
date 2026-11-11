@local @local_pspvinvoice @pspv
Feature: Invoices of the campus shop
  In order to keep the accounts of the courses sold
  As the hospital
  I need buyers to give their billing details and every sale to get an invoice

  Background:
    Given the following config values are set as admin:
      | category   | shop-catalog | local_pspvshop |
      | testmode   | 1            | local_pspvshop |
    And the following "categories" exist:
      | name     | category | idnumber     |
      | Nursing  | 0        | shop-catalog |
    And the following "courses" exist:
      | fullname           | shortname | category     |
      | Chronic wound care | WOUND     | shop-catalog |
    And the following "local_pspvshop > products" exist:
      | course | price |
      | WOUND  | 75    |

  Scenario: A purchase asks for the billing details and ends with an invoice
    Given I log in as "admin"
    And I visit "/local/pspvshop/index.php"
    And I follow "Chronic wound care"
    And I press "Add to cart"
    When I press "Continue to payment"
    Then I should see "Billing details"
    And I set the following fields to these values:
      | Name or company name | Bea Buyer       |
      | Tax ID (NIF/CIF)     | 12345678-Z      |
      | Address              | Example St. 1   |
      | Postcode             | 08001           |
      | City/town            | Barcelona       |
    And I press "Continue to payment"
    And I should see "75.00" in the "[data-region='order-total']" "css_element"
    And I press "Simulate the payment"
    And I should see "Payment received."
    And "Invoice F-" "link" should exist
    And I navigate to "Plugins > Local plugins > Campus PSPV invoicing > Invoices" in site administration
    And I should see "Bea Buyer"
    And I should see "12345678Z"
    And I should see "75.00"

  Scenario: Refunding an order issues the credit note
    Given I log in as "admin"
    And I visit "/local/pspvshop/index.php"
    And I follow "Chronic wound care"
    And I press "Add to cart"
    And I press "Continue to payment"
    And I set the following fields to these values:
      | Name or company name | Bea Buyer       |
      | Tax ID (NIF/CIF)     | 12345678Z       |
      | Address              | Example St. 1   |
      | Postcode             | 08001           |
      | City/town            | Barcelona       |
    And I press "Continue to payment"
    And I press "Simulate the payment"
    When I navigate to "Plugins > Local plugins > Campus PSPV shop > Orders" in site administration
    And I click on "Refund" "link" in the "Admin User" "table_row"
    And I press "Continue"
    Then I should see "has been marked as refunded"
    And I navigate to "Plugins > Local plugins > Campus PSPV invoicing > Invoices" in site administration
    And I should see "Credit note"
    And I should see "-75.00"

  Scenario: The billing form checks the tax ID
    Given I log in as "admin"
    And I visit "/local/pspvshop/index.php"
    And I follow "Chronic wound care"
    And I press "Add to cart"
    And I press "Continue to payment"
    When I set the following fields to these values:
      | Name or company name | Bea Buyer     |
      | Tax ID (NIF/CIF)     | 12            |
      | Address              | Example St. 1 |
      | Postcode             | 08001         |
      | City/town            | Barcelona     |
    And I press "Continue to payment"
    Then I should see "Enter the tax ID the invoice has to carry."
