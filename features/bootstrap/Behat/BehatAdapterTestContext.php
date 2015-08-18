<?php
use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode, Behat\Gherkin\Node\TableNode;
use bdd_videoannotator\stub_php\stringArrayArray;
use bdd_videoannotator\stub_php\stringArray;


/**
 *  SubContext-Class for behat_support.feature
 *
 *  PHP version 5
 *
 * @category Class
 * @package  Features/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 */

class BehatAdapterTestContext extends BehatContext
{
    private $_adapterWithoutServerConnection;
    private $_mockedClient;
    private $_subTestDirectory;
    private $_classNameFeatureContext;
    private $_tempString;

    private $_cntScenariosToBeStopped = 0;

    /**
     * @Given /^I have an instance of the BDD-Adapter for Behat without a server connection$/
     */
    public function iHaveAnInstanceOfTheBddAdapterForBehatWithoutAServerConnection()
    {
        $mockedServerConnector = Phake::mock("bdd_videoannotator\bddadapters\ServerConnector");
        $this->_mockedClient = Phake::mock("bdd_videoannotator\stub_php\AnnotationServiceService");
        Phake::when($mockedServerConnector)->startServer->thenReturn($this->_mockedClient);
        $this->_adapterWithoutServerConnection = new \bdd_videoannotator\bddadapters\BehatReportingAdapter ($mockedServerConnector);
        Phake::verify($mockedServerConnector)->startServer();
    }


    /**
     * @When /^the Adapter reports "([^"]*)" with exception "([^"]*)" it should be converted to "([^"]*)"$/
     */
    public function theAdapterReportsWithExceptionItShouldBeConvertedTo($behatResult, $exceptionType, $expected)
    {
        $mockedStepEvent = $this->getMockForStepEvent($behatResult, $exceptionType);
        $this->verifyResultConversion($mockedStepEvent, $expected);
        Phake::verify($mockedStepEvent)->getException();
    }

    /**
     * @When /^the Adapter reports "([^"]*)" with exception - it should be converted to "([^"]*)"$/
     */
    public function theAdapterReportsWithoutExceptionItShouldBeConvertedTo($behatResult, $expected)
    {
        $mockedStepEvent = $this->getMockForStepEvent($behatResult);
        $this->verifyResultConversion($mockedStepEvent, $expected);
    }

    private function getMockForStepEvent($behatResult, $exceptionType = null)
    {
        $mockedStepEvent = Phake::mock("Behat\Behat\Event\StepEvent");
        $resultCode = constant("Behat\Behat\Event\StepEvent::{$behatResult}");
        Phake::when($mockedStepEvent)->getResult->thenReturn($resultCode);

        if (isset($exceptionType)) {
            Phake::when($mockedStepEvent)->hasException->thenReturn(true);
            $mockedException = Phake::mock($exceptionType);
            Phake::when($mockedStepEvent)->getException->thenReturn($mockedException);
        } else {
            Phake::when($mockedStepEvent)->hasException->thenReturn(false);
        }
        return $mockedStepEvent;
    }

    private function verifyResultConversion($mockedStepEvent, $expectedResult)
    {
        $actualRes = $this->_adapterWithoutServerConnection->convertResultToStepResult($mockedStepEvent);
        PHPUnit_Framework_Assert::assertEquals($expectedResult, $actualRes);
        Phake::verify($mockedStepEvent)->getResult();
    }

    /**
     * @When /^I convert a pystringObject with:$/
     */
    public function iConvertAPystringobjectWith(PyStringNode $pyObject)
    {
        $this->_tempString = $this->_adapterWithoutServerConnection->convertPyStringToNormalString($pyObject);
    }

    /**
     * @Then /^I should get a string "([^"]*)" with pystring delimiters and a single intent$/
     */
    public function iShouldGetAStringWithPystringDelimiters($expectedString)
    {
        $intent = " ";
        $expected = "$intent\"\"\"" . "\n" . $intent . $expectedString . "\n$intent\"\"\"";
        PHPUnit_Framework_Assert::assertEquals($expected, $this->_tempString);
    }

    /**
     * @Given /^I have a feature file:$/
     */
    public function iHaveAFeatureFile(PyStringNode $content)
    {
        $this->_subTestDirectory = SubTestHelper::getNewSubTestDirectory();
        $this->_classNameFeatureContext = strtoupper(basename($this->_subTestDirectory));
        $featureDirectory = $this->_subTestDirectory . DIRECTORY_SEPARATOR . "features";
        $created = mkdir($featureDirectory);

        $fhandle = fopen($featureDirectory . DIRECTORY_SEPARATOR . "subtest.feature", "w+");
        fwrite($fhandle, $content);
        fclose($fhandle);

        // write also sample featurecontext => Behat will exit otherwise
        $bootsStrapFolder = $featureDirectory . DIRECTORY_SEPARATOR . "bootstrap";
        $created = mkdir($bootsStrapFolder);
        $fhandle = fopen($bootsStrapFolder . DIRECTORY_SEPARATOR . $this->_classNameFeatureContext . ".php", "w+");
        // HEREDOCSYNTAX
        $contentSampleFile = <<<CODE
<?php

				
use Behat\Behat\Context\BehatContext;
use Behat\Behat\Exception\PendingException;

class $this->_classNameFeatureContext extends BehatContext { 			

 
};
CODE;
        fwrite($fhandle, $contentSampleFile);
        fclose($fhandle);
    }

    /**
     * @When /^I run Behat$/
     */
    public function iRunBehatWithAMockedAdapter()
    {
        $behatSubTestExecutor = new BehatSubTestExecutorProxy ($this->_subTestDirectory, $this->_classNameFeatureContext, $this->_adapterWithoutServerConnection);
        $behatSubTestExecutor->executeSubTest();
    }


    /**
     * @Then /^the Adapter should report the feature "([^"]*)"$/
     */
    public function theAdapterShouldReportTheFeature($expectedFeatureText)
    {
        Phake::verify($this->_mockedClient)->setFeatureText($expectedFeatureText);
    }

    
    /**
     * @Then /^the Adapter should report the scenario "([^"]*)" with following steps:$/
     */
    public function theAdapterShouldReportTheScenarioWithSteps($scenarioName, PyStringNode $expected_steps)
    {
        Phake::verify($this->_mockedClient, Phake::atLeast(1))->startScenario($scenarioName);

        // some steptext might be more than once in the result array
        $timesOccured = array_count_values($expected_steps->getLines());

        foreach ($expected_steps->getLines() as $steptext) {
            $times_expected = $timesOccured [$steptext];
            Phake::verify($this->_mockedClient, Phake::times($times_expected))->addStepToBuffer($steptext, null);
        }
        $this->_cntScenariosToBeStopped++;
        Phake::verify($this->_mockedClient, Phake::atLeast($this->_cntScenariosToBeStopped))->stopScenario();
    }

    /**
     * @Then /^the Adapter should send the steptext: "([^"]*)" with the datatable:$/
     */
    public function theAdapterShouldSendTheSteptextWithTheDatatable($expectedStepText, TableNode $expectedTable)
    {
        $datatableExpected = $this->convertDataTable2StringArrayArray($expectedTable);
        Phake::verify($this->_mockedClient)->addStepToBuffer($expectedStepText, $datatableExpected);
    }

    private function convertDataTable2StringArrayArray(TableNode $table)
    {
        $datatable = new stringArrayArray();
        $datatable->item = array();
        foreach ($table->getRows() as $tableRow) {
            $stringArrayRow = new stringArray();
            $stringArrayRow->item = $tableRow;
            array_push($datatable->item, $stringArrayRow);
        }
        return $datatable;
    }

    /**
     * @Given /^I have a feature file with a step "([^"]*)" and a docstring "([^"]*)"$/
     */
    public function iHaveAFeatureFileWithAStepAndADocstring($stepText, $docString)
    {
        $contentsFeatureFile = "Feature: test";
        $contentsFeatureFile .= "\nScenario: test";
        $contentsFeatureFile .= "\n" . $stepText;
        $contentsFeatureFile .= "\n\"\"\"\n" . $docString . "\n\"\"\"";
        $this->iHaveAFeatureFile(new PyStringNode ($contentsFeatureFile));
    }

    /**
     * @Then /^the Adapter should report the step as follows:$/
     */
    public function theAdapterShouldReportTheStepAsFollows(PyStringNode $expected)
    {
        $expectedText = ( string )$expected;
        $expectedText = str_replace("<DOCSTRING>", '"""', $expectedText);
        Phake::verify($this->_mockedClient)->addStepToBuffer($expectedText, null);
    }

    /**
     * @Given /^the Adapter should send "([^"]*)" (\d+) times to the server$/
     */
    public function theAdapterShouldSendTimesToTheServer($expectedResult, $cntTimes)
    {
        Phake::verify($this->_mockedClient, Phake::times($cntTimes))->addResultToBufferStep($expectedResult);
    }
}