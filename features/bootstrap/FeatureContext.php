<?php

/**  
 *  FeatureContext-classes for behat.
 *  
 *  PHP version 5
 *
 *  @category Class
 *  @package  Features/
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     TODO
 */

use Behat\Behat\Context\ClosuredContextInterface, 
Behat\Behat\Context\TranslatedContextInterface, 
Behat\Behat\Context\BehatContext,
 Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode, Behat\Gherkin\Node\TableNode;


use bdd_videoannotator\bddadapters\ServerConnector;

require_once 'lib_helper.php';

/**  
 *  FeatureContext-Class
 *  
 *  @category Class
 *  @package  Features/
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     TODO
 */

class FeatureContext extends BehatContext
{

    private $_connector;
    private $_adapterWithoutServer;
    private $_nameTestScenario;
   
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        
    }

    
    /**
     * @BeforeSuite
     */
    public static function testdir($event)
    {
        $testdir = "test_output";
        rmr($testdir);
        mkdir($testdir);
        chdir($testdir);
    }

    /**
     * @Given /^I start the server from the provided client package$/
     */
    public function iStartTheServerFromTheProvidedClientPackage()
    {
        $this->connector = new \bdd_videoannotator\bddadapters\ServerConnector();
        $this->connector->startServer();
    }

    /**
     * @Then /^i must be able to connect to the server functions without an error$/
     */
    public function iMustBeAbleToConnectToTheServerFunctionsWithoutAnError()
    {
        $this->connector->getServerClient()->changeOutputDirectory(".");
    }

    /**
     * @Given /^i must be able to stop the server$/
     */
    public function iMustBeAbleToStopTheServer()
    {
        $is_stopped = $this->connector->stopServer();
        PHPUnit_Framework_Assert::assertTrue($is_stopped, "Server Process not stopped");
    }

    /**
     * @When /^i start a new scenario$/
     */
    public function iStartANewScenario()
    {
        $this->_nameTestScenario = "Testscenario";
        $this->connector->getServerClient()->startScenario($this->_nameTestScenario);
    }

    /**
     * @Given /^I add a samplestep to the scenario$/
     */
    public function iAddASamplestepToTheScenario()
    {
        $this->connector->getServerClient()->addStepToBuffer("Teststep", null);
    }

    /**
     * @Given /^I stop the scenario$/
     */
    public function iStopTheScenario()
    {
        $this->connector->getServerClient()->stopScenario();
    }

    /**
     * @Then /^i should get an annotation file$/
     */
    public function iShouldGetAnAnnotationFile()
    {
        $arr_contents = getAllFilesInCurrentDirectory();
        foreach ($arr_contents as $entry) {
            if (strpos($entry, $this->_nameTestScenario) !== false && strpos($entry, ".eaf") !== false)
                return;
        }
        PHPUnit_Framework_Assert::assertTrue(false, "No matching annotation file found for " . $this->_nameTestScenario);
    }

    /**
     * @Given /^i should get an video file$/
     */
    public function iShouldGetAnVideoFile()
    {
        $arr_contents = getAllFilesInCurrentDirectory();
        foreach ($arr_contents as $entry) {
            if (strpos($entry, $this->_nameTestScenario) !== false
                && strpos($entry, ".avi") !== false
            ) {
                return;
            }
        }
        PHPUnit_Framework_Assert::assertTrue(false, "No matching video file found for " . $this->_nameTestScenario);
    }

    /**
     * @Given /^I have an instance of the BDD-Adapter for Behat without a server connection$/
     */
    public function iHaveAnInstanceOfTheBddAdapterForBehatWithoutAServerConnection()
    {
        // NOTICE: MockBuilder of PHPUnit is bound to PHPUnit_Framework_TestCase
        $sample_test_case = new SampleTestCase();
        $stubbed_connector = $sample_test_case->getMockBuilder("bdd_videoannotator\bddadapters\ServerConnector")
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapterWithoutConnection = new \bdd_videoannotator\bddadapters\BehatReportingAdapter($stubbed_connector);
    }
    

    /**
     * @When /^the Adapter reports "([^"]*)" with exception  it should be converted to "([^"]*)"$/
     */
    public function theAdapterReportsWithExceptionItShouldBeConvertedTo($behatResult, $expected)
    {
    	$sample_test_case = new SampleTestCase();
    	$mockedStepEvent = $sample_test_case->getMockBuilder("Behat\Behat\Event\StepEvent")
    	->disableOriginalConstructor()
    	->getMock();
    	
    	$resultCode = constant("Behat\Behat\Event\StepEvent::{$behatResult}");
    	$mockedStepEvent->method('hasException')->willReturn(false);
    	$mockedStepEvent->method('getResult')->willReturn($resultCode);
    	
    	$actualRes = $this->adapterWithoutConnection->convertResultToStepResult($mockedStepEvent);
    	PHPUnit_Framework_Assert::assertEquals($expected, $actualRes, "FROMRES: $behatResult");
    }
    
    /**
     * @When /^the Adapter reports "([^"]*)" with exception "([^"]*)" it should be converted to "([^"]*)"$/
     */
    public function theAdapterReportsWithExceptionItShouldBeConvertedTo2($behatResult, $exceptionType, $expected)
    {
    	$sample_test_case = new SampleTestCase();
    	$mockedStepEvent = $sample_test_case->getMockBuilder("Behat\Behat\Event\StepEvent")
    	->disableOriginalConstructor()
    	->getMock();
    	
    	$mockedStepEvent->method('hasException')->willReturn(true);
      	$mockedStepEvent->method('getResult')->willReturn(constant("Behat\Behat\Event\StepEvent::FAILED"));
      	
      	$mockedException = $sample_test_case->getMockBuilder($exceptionType)->disableOriginalConstructor()->getMock();
      	$mockedStepEvent->method('getException')->willReturn($mockedException);

      	$actualRes = $this->adapterWithoutConnection->convertResultToStepResult($mockedStepEvent); 	
      	PHPUnit_Framework_Assert::assertEquals($expected, $actualRes, "FROMRES: $behatResult");
      	
    }
    
}

/**
 * Sample class to use PHPUnits MockBuilder.
 * @author Hell <stefan.hell@gmail.com>
 * 
 */

class SampleTestCase extends PHPUnit_Framework_TestCase
{
}
