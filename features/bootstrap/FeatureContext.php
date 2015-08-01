<?php

/**
 *  FeatureContext-classes for behat.
 *
 *  PHP version 5
 *
 * @category Class
 * @package  Features/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 */

use Behat\Behat\Context\BehatContext;

require_once 'lib_helper.php';


class FeatureContext extends BehatContext
{

    public function __construct(array $parameters)
    {
        $this->useContext('MainUsageContext', new MainUsageContext());
        $this->useContext('BehatAdapterTestContext', new BehatAdapterTestContext());
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


}

