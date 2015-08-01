<?php

use Behat\Behat\Context\BehatContext;


/**
 *  SubContext-class for main_usage.feature.
 *
 *  PHP version 5
 *
 * @category Class
 * @package  Features/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 */

class MainUsageContext extends BehatContext
{

    private $_connector;

    /**
     * @Given /^I start the server from the provided client package$/
     */
    public function iStartTheServerFromTheProvidedClientPackage()
    {
        $this->_connector = new \bdd_videoannotator\bddadapters\ServerConnector();
        $this->_connector->startServer();
    }

    /**
     * @Then /^i must be able to connect to the server functions without an error$/
     */
    public function iMustBeAbleToConnectToTheServerFunctionsWithoutAnError()
    {
        $this->_connector->getServerClient()->changeOutputDirectory(".");
    }

    /**
     * @Given /^i must be able to stop the server$/
     */
    public function iMustBeAbleToStopTheServer()
    {
        $is_stopped = $this->_connector->stopServer();
        PHPUnit_Framework_Assert::assertTrue($is_stopped, "Server Process not stopped");
    }

}

