<?php

/**
 *  Proxy which is used in BehatSubTestExcecutor:
 *  A mocked instance of BehatReportingAdapter must be set (static) at the beginning of a testrun.
 *  Each call will be redirected directly to the mock object, wich can be verified afterwards.
 *  PHP version 5
 *
 * @category Class
 * @package  Features/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 */

use bdd_videoannotator\bddadapters\BehatReportingAdapter;
use Behat\Behat\Event\OutlineEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Behat\Event\FeatureEvent;
use Behat\Behat\Exception\FormatterException;
use Behat\Behat\Formatter\FormatterInterface;
use Symfony\Component\Translation\Translator;

class BehatReportingAdapterTestProxy implements FormatterInterface
{

    private static $_behatReportingAdapter;

    public function __construct()
    {
        if (!isset(self::$_behatReportingAdapter)) {
            throw new FormatterException("No Adapter instance for proxy set!");
        }
    }

    public static function getUnderlyingInstance()
    {
        return self::$_behatReportingAdapter;
    }

    public static function setUnderlyingInstance(BehatReportingAdapter $adapterInstance)
    {
        self::$_behatReportingAdapter = $adapterInstance;
    }


    public function setTranslator(Translator $translator)
    {

        self::$_behatReportingAdapter->setTranslator($translator);
    }


    public function hasParameter($name)
    {
        return self::$_behatReportingAdapter->hasParameter($name);
    }


    public function setParameter($name, $value)
    {
        self::$_behatReportingAdapter->setParameter($name, $value);
    }

    public function getParameter($name)
    {
        return self::$_behatReportingAdapter->getParamter($name);
    }


    public static function getSubscribedEvents()
    {
        return self::$_behatReportingAdapter->getSubscribedEvents();
    }

    public function beforeScenario(ScenarioEvent $event)
    {
        self::$_behatReportingAdapter->beforeScenario($event);
    }


    public function afterScenario(ScenarioEvent $event)
    {
        self::$_behatReportingAdapter->afterScenario($event);
    }

    public function beforeOutline(OutlineEvent $event)
    {
        self::$_behatReportingAdapter->beforeOutline($event);
    }

    public function afterOutline(OutlineEvent $event)
    {
        self::$_behatReportingAdapter->afterOutline($event);
    }

    public function beforeFeature(FeatureEvent $event){
        self::$_behatReportingAdapter->beforeFeature($event);
    }

    public function beforeStep(StepEvent $event)
    {
        self::$_behatReportingAdapter->beforeStep($event);
    }

    public function afterStep(StepEvent $event)
    {
        self::$_behatReportingAdapter->afterStep($event);
    }
}