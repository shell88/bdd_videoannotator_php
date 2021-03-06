<?php

namespace bdd_videoannotator\bddadapters;

use bdd_videoannotator\stub_php;
use bdd_videoannotator\stub_php\stringArray;
use bdd_videoannotator\stub_php\stringArrayArray;
use Behat\Behat\Event\FeatureEvent;
use Behat\Behat\Event\OutlineEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Behat\Formatter\FormatterInterface;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\Translation\Translator;

/**
 *  Behat Reporting Adapter for use of bdd_videoannotator.
 *  To use the Adapter call behat with option
 *  -f bdd_videoannotator\bddadapters\BehatReportingAdapter
 *  PHP version 5
 *
 * @category Class
 * @package  Bdd_Videoannotator
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 *
 */
class BehatReportingAdapter implements FormatterInterface
{
    private $_translator;
    private $_client;

    /**
     * Reads the properties from adapter_config.ini and
     * starts the annotationServer-Process.
     *
     * @param string $server_connector - optional server_connector
     */
    public function __construct($server_connector = null)
    {
        if ($server_connector == null
            && !($server_connector instanceof ServerConnector)
        ) {
            $server_connector = new ServerConnector();
        }
        $this->_client = $server_connector->startServer();
    }

    /**
     * Sets the translator for the outputLanguage.
     * It is never used by bdd_videoannotator.
     *
     * @param Translator $translator the translator that will be set.
     */
    public function setTranslator(Translator $translator)
    {
        $this->_translator = $translator;
    }

    /**
     * Checks if current formatter has parameter.
     *
     * @param string $name name of the parameter.
     *
     * @return Boolean alwas returns false as Formatter will be configured
     * using config file.
     */
    public function hasParameter($name)
    {
        return false;
    }

    /**
     * Used to set a paremter value from the command line
     * Not used.
     *
     * @param $name string Name of the parameter.
     * @param $value string Value of the parameter.
     */
    public function setParameter($name, $value)
    {
        //No implementation 
    }

    /**
     * Returns a parameter Value.
     * Not used.
     *
     * @param $name string Name of the parameter.
     * @return null
     */
    public function getParameter($name)
    {
        //No implementation
    }

    /**
     * Subscribes to Events of Behat.
     *
     * @return array Array with the eventNames.
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'beforeFeature',
            'beforeScenario',
            'afterScenario',
            'beforeOutline',
            'afterOutline',
            'beforeStep',
            'afterStep'
        );
        return array_combine($events, $events);
    }

    public function beforeFeature(FeatureEvent $event)
    {
        $this->_client->setFeatureText(
            $event->getFeature()->getTitle()
        );
    }


    /**
     * Listens to "scenario.before" event.
     *
     * @param ScenarioEvent $event Event that contains the scenario.
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        $this->_client->startScenario(
            $event->getScenario()->getTitle()
        );
    }

    /**
     * Listens to "scenario.after" event.
     *
     * @param ScenarioEvent $event Event that contains the scenario.
     *
     * @uses printTestCase()
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $this->_client->stopScenario();
    }

    /**
     * Listens to "outline.example.before" event. Starts the scenario on the server.
     *
     * @param $event OutlineEvent Event that contains the scenarioOutline.
     */
    public function beforeOutline(OutlineEvent $event)
    {
        $this->_client->startScenario(
            $event->getOutline()->getTitle()
        );
    }

    /**
     * Stops the scenario on the server.
     *
     * @param OutlineEvent $event Event containing scenario-Information.
     */
    public function afterOutline(OutlineEvent $event)
    {
        $this->_client->stopScenario();
    }

    /**
     * Adds a step to the stepBuffer on the server.
     *
     * @param StepEvent $event - The step to be added on the server.
     */
    public function beforeStep(StepEvent $event)
    {
        $steptext = $event->getStep()->getType() . " " . $event->getStep()->getText();
        $stepdata = null;

        foreach ($event->getStep()->getArguments() as $argument) {
            if ($argument instanceof PyStringNode) {
                $steptext .= "\n" . $this->convertPyStringToNormalString($argument);
            } elseif ($argument instanceof TableNode) {
                $stepdata = $this->convertTableNodeToServerStringArray($argument);
            }
        }
        $this->_client->addStepToBuffer($steptext, $stepdata);
    }

    /**
     * Adds the result of the stepExceution to the server.
     *
     * @param StepEvent $event The executed Step.
     */
    public function afterStep(StepEvent $event)
    {
        $this->_client->addResultToBufferStep($this->convertResultToStepResult($event));
    }

    /**
     * Converts the behat-Result to the serverSide format.
     *
     * @param StepEvent $event The event containing the behat StepResult.
     * @return stub_php\stepResult
     */
    public function convertResultToStepResult(StepEvent $event)
    {
        switch ($event->getResult()) {
            case StepEvent::PASSED:
                return stub_php\stepResult::SUCCESS;
            case StepEvent::UNDEFINED:
            case StepEvent::PENDING:
            case StepEvent::SKIPPED:
                return stub_php\stepResult::SKIPPED;
            case StepEvent::FAILED:
                if ($event->hasException() && !$this->_isAssertionError($event->getException())) {
                    return stub_php\stepResult::ERROR;
                } else {
                    return stub_php\stepResult::FAILURE;
                }
            default:
                return stub_php\stepResult::ERROR;
        }
    }

    /**
     * Converts a PyStringObject to a normal stringObject with intents
     *
     * @param PyStringNode $pynode
     *
     * @return string
     */

    public function convertPyStringToNormalString(PyStringNode $pynode)
    {
        $intent = " ";
        $intentedPyNodeText = "";
        foreach ($pynode->getLines() as $pynodeLine) {
            $intentedPyNodeText .= $intent . $pynodeLine . "\n";
        }
        return sprintf("$intent\"\"\"\n%s$intent\"\"\"", $intentedPyNodeText);
    }

    public function convertTableNodeToServerStringArray(TableNode $tnode)
    {
        $arr_object = array();
        foreach ($tnode->getRows() as $row) {
            $obj = new stringArray();
            $obj->item = $row;
            array_push($arr_object, $obj);
        }
        $serverStringArray = new stringArrayArray();
        $serverStringArray->item = $arr_object;
        return $serverStringArray;
    }

    /**
     * Checks if an exception is an assertionError
     *
     * @param \Exception $obj The object to check
     *
     * @return boolean     true if $obj is an assertionError
     */

    private function _isAssertionError($obj)
    {
        if (is_a($obj, "PHPUnit_Framework_AssertionFailedError")
            || is_a($obj, "Behat\Mink\Exception\ExpectationException")
        ) {
            return true;
        }
        return false;
    }
} 