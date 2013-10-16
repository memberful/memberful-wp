<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }

    /**
     * @Given /^I have a WordPress site that is connected to Memberful$/
     */
    public function iHaveAWordpressSiteThatIsConnectedToMemberful()
    {
        throw new PendingException();
    }

    /**
     * @Given /^there is post that can only be viewed by people with a subscription$/
     */
    public function thereIsPostThatCanOnlyBeViewedByPeopleWithASubscription()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I am signed as a member who has not purchased that subscription$/
     */
    public function iAmSignedAsAMemberWhoHasNotPurchasedThatSubscription()
    {
        throw new PendingException();
    }

    /**
     * @When /^I view the post$/
     */
    public function iViewThePost()
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should see the marketing material$/
     */
    public function iShouldSeeTheMarketingMaterial()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I should not see the post content$/
     */
    public function iShouldNotSeeThePostContent()
    {
        throw new PendingException();
    }

    /**
     * @Given /^there is a post that can only be viewed by people with a subscription$/
     */
    public function thereIsAPostThatCanOnlyBeViewedByPeopleWithASubscription()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I am signed in a member who has purchased the subscription$/
     */
    public function iAmSignedInAMemberWhoHasPurchasedTheSubscription()
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should see the post content$/
     */
    public function iShouldSeeThePostContent()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I should not see the marketing material$/
     */
    public function iShouldNotSeeTheMarketingMaterial()
    {
        throw new PendingException();
    }

    /**
     * @Given /^there is a post that is not protected$/
     */
    public function thereIsAPostThatIsNotProtected()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I am not signed in$/
     */
    public function iAmNotSignedIn()
    {
        throw new PendingException();
    }
}
