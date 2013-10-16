Feature: Protecting content
  In order to sell my amazing content to people
  I need to make sure they can't access it before they buy it

  Background:
    Given I have a WordPress site that is connected to Memberful

  Scenario: Trying to view a protected post without purchasing subscription
    Given there is post that can only be viewed by people with a subscription
    And I am signed as a member who has not purchased that subscription
    When I view the post
    Then I should see the marketing material
    And I should not see the post content

  Scenario: Viewing a protected post as a customer who's bought the subscription
    Given there is a post that can only be viewed by people with a subscription
    And I am signed in a member who has purchased the subscription
    When I view the post
    Then I should see the post content
    And I should not see the marketing material

  Scenario: Viewing an unprotected post
    Given there is a post that is not protected
    And I am not signed in
    When I view the post
    Then I should see the post content
