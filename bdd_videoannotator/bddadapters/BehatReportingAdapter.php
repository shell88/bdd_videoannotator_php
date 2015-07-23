<?php

/**
 *  PHP-based Reporting Adapter for use of bdd_videoannotator.
 * 
 *  PHP version 5
 *  
 *  @category Class
 *  @package  Bdd_Videoannotator/BDDAdapters
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     https://github.com/shell88/bdd_videoannotator
 *  
 */
namespace bdd_videoannotator\bddadapters;

use bdd_videoannotator\stub_php;
use Symfony\Component\Translation\Translator;
use Behat\Behat\Formatter\FormatterInterface;
use Behat\Behat\Event\EventInterface, Behat\Behat\Event\FeatureEvent, Behat\Behat\Event\ScenarioEvent, Behat\Behat\Event\OutlineEvent, Behat\Behat\Event\StepEvent;
use Behat\Gherkin\Node\FeatureNode, Behat\Gherkin\Node\ScenarioNode, Behat\Gherkin\Node\StepNode, Behat\Behat\Exception\FormatterException, Behat\Gherkin\Node\PyStringNode, Behat\Gherkin\Node\TableNode;
use bdd_videoannotator\stub_php\stringArray;
use bdd_videoannotator\stub_php\stringArrayArray;

/**
 * Behat Reporting Adapter for use of bdd_videoannotator.
 * To use the Adapter call behat with option
 * -f bdd_videoannotator\bddadapters\BehatReportingAdapter
 * PHP version 5
 *
 * @category Class
 * @package  Bdd_Videoannotator
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0
 *           http://www.apache.org/licenses/LICENSE-2.0.txt
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
     * @param string $server_connector
     *            - optional server_connector
     */
    public function __construct($server_connector = null)
    {
        if ($server_connector == null && ! ($server_connector instanceof ServerConnector)) {
            $server_connector = new ServerConnector();
        }
        $this->_client = $server_connector->startServer();
    }

    /**
     * Sets the translator for the outputLanguage.
     *
     * It is never used by bdd_videoannotator.
     *
     * @param Translator $translator
     *            the translator that will be set.
     *            
     * @return nothing
     */
    public function setTranslator(Translator $translator)
    {
        $this->_translator = $translator;
    }

    /**
     * Checks if current formatter has parameter.
     *
     * @param string $name
     *            name of the parameter.
     *            
     * @return Boolean alwas returns false as Formatter will be configured
     *         using config file.
     */
    public function hasParameter($name)
    {
        return false;
    }

    /**
     * Used to set a paremter value from the command line
     * Not used.
     *
     * @param unknown $name
     *            Name of the parameter.
     * @param unknown $value
     *            Value of the parameter.
     *            
     * @return nothing
     */
    public function setParameter($name, $value)
    {
        // No implementation
    }

    /**
     * Returns a parameter Value.
     * Not used.
     *
     * @param unknown $name
     *            Name of the parameter.
     *            
     * @return nothing
     */
    public function getParameter($name)
    {
        // No implementation
    }

    /**
     * Subscribes to Events of Behat.
     *
     * @return multitype Array with the eventNames.
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'beforeScenario',
            'afterScenario',
            'beforeOutline',
            'afterOutline',
            'beforeStep',
            'afterStep'
        );
        
        return array_combine($events, $events);
    }

    /**
     * Listens to "scenario.before" event.
     *
     * @param ScenarioEvent $event
     *            Event that contains the scenario.
     *            
     * @return nothing
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        $this->_client->startScenario($event->getScenario()
            ->getTitle());
    }

    /**
     * Listens to "scenario.after" event.
     *
     * @param ScenarioEvent $event
     *            Event that contains the scenario.
     *            
     * @return nothing
     *
     * @uses printTestCase()
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $this->_client->stopScenario();
    }

    /**
     * Listens to "outline.example.before" event.
     * Starts the scenario on the server.
     *
     * @param OutlineExampleEvent $event
     *            Event that contains the scenarioOutline.
     *            
     * @return nothing
     */
    public function beforeOutline(OutlineEvent $event)
    {
        $this->_client->startScenario($event->getOutline()
            ->getTitle());
    }

    /**
     * Stops the scenario on the server.
     *
     * @param OutlineEvent $event
     *            Event containing scenario-Information.
     *            
     * @return nothing
     */
    public function afterOutline(OutlineEvent $event)
    {
        $this->_client->stopScenario();
    }

    /**
     * Adds a step to the stepBuffer on the server.
     *
     * @param StepEvent $event
     *            - The step to be added on the server.
     *            
     * @return nothing
     */
    public function beforeStep(StepEvent $event)
    {
        $steptext = $event->getStep()->getText();
        $stepdata = null;
        
        foreach ($event->getStep()->getArguments() as $argument) {
            if ($argument instanceof PyStringNode) {
                $steptext .= $this->convertPyStringToNormalString($argument);
            } elseif ($argument instanceof TableNode) {
                $stepdata = $this->convertTableNodeToServerStringArray($argument);
            }
        }
        
        $this->_client->addStepToBuffer($steptext, $stepdata);
    }

    /**
     * Adds the result of the stepExceution to the server.
     *
     * @param StepEvent $event
     *            The executed Step.
     *            
     * @return nothing
     */
    public function afterStep(StepEvent $event)
    {
        $this->_client->addResultToBufferStep($this->convertResultToStepResult($event));
    }

    /**
     * Converts the behat-Result to the serverSide format.
     *
     * @param StepEvent $event
     *            The event containing the behat StepResult.
     *            
     * @return string
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
                if ($event->hasException() && ! $this->_isAssertionError($event->getException())) {
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
        $indent = " ";
        $string = strtr(sprintf("$indent\"\"\"\n%s\n\"\"\"", (string) $pynode), array(
            "\n" => "\n$indent"
        ));
        return $string;
    }

    /**
     * Converts a TableNode to a StringArrayArray-Object for transmission to server
     * @param TableNode $tnode
     * @return \bdd_videoannotator\stub_php\stringArrayArray
     */
    
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
     * @param unknown $obj
     *            The object to check
     *            
     * @return boolean true if $obj is an assertionError
     */
    private function _isAssertionError($obj)
    {
        if (is_a($obj, "PHPUnit_Framework_AssertionFailedError") || is_a($obj, "Behat\Mink\Exception\ExpectationException")) {
            return true;
        }
        return false;
    }
} 