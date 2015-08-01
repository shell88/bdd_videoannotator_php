<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *  PHP version 5
 *
 * Starts a subtest in Behat using BehatReportingAdapterTestProxy.
 *
 * @category Class
 * @package  Features/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 */


class BehatSubTestExecutorProxy
{

    private $_subtestfolder;
    private $_behatInstance;

    /**
     * @param $subtestfolder       - Folder in which the subtest is executed, containing feature-Files and bootstrap-Folder
     * @param $classNameContext    - The Name for the Base-Context-Class to be set
     * @param \bdd_videoannotator\ - $adapter_instance - Instance which should be used in the subtest
     * bddadapters\
     * BehatReportingAdapter
     */
    public function __construct($subtestfolder, $classNameContext, \bdd_videoannotator\bddadapters\BehatReportingAdapter $adapter_instance)
    {
        $this->_subtestfolder = $subtestfolder;
        BehatReportingAdapterTestProxy::setUnderlyingInstance($adapter_instance);
        $this->_behatInstance = new BehatSubApplicationProxy($classNameContext);
        $this->_behatInstance->setAutoExit(false);

    }

    public function executeSubTest()
    {
        $old_dir = __DIR__;
        chdir($this->_subtestfolder);
        echo "\n---\nSTARTING SUBTEST IN " . $this->_subtestfolder . "\n";
        $this->_behatInstance->run();
        chdir($old_dir);
    }

}

/**
 *  PHP version 5
 *  A Behat-Application for doing a subtest with
 *  BehatReportingAdapterTestProxy as formatter and a specific ContextClassName.
 *
 * @category Class
 * @package  Features/
 * @author   Stefan Hell <stefan.hell88@gmail.com>
 * @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 * @link     https://github.com/shell88/bdd_videoannotator
 */


class BehatSubApplicationProxy extends Behat\Behat\Console\BehatApplication
{

    private $_classNameContext;

    public function __construct($classNameContext)
    {
        parent::__construct(BEHAT_VERSION);
        $this->_classNameContext = $classNameContext;
    }

    /**
     * Creates container instance, loads extensions and freezes it.
     *
     * @param InputInterface $input
     *
     * @return ContainerInterface
     */
    protected function createContainer($input)
    {
        $container = new ContainerBuilder();
        $this->loadCoreExtension($container, $this->loadConfiguration($container, $input));

        $container->setParameter("behat.formatter.name", "BehatReportingAdapterTestProxy");
        $container->setParameter("behat.context.class", $this->_classNameContext);

        $container->compile();
        return $container;
    }

}