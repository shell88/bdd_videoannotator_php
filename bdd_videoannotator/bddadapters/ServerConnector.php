<?php

/**
 *  PHP-based ReportingAdapters for use of bdd_videoannotator.
 *
 *  PHP version 5
 *
 *  @category Class
 *  @package  Bdd_Videoannotator/BDDAdapters
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     TODO
 *
 */
namespace bdd_videoannotator\bddadapters;

use bdd_videoannotator\bddadapters\ServerConnectorException;

/**
 *  Reads config from adapter_config.ini and starts the annotationServer.
 *
 *  PHP version 5
 *
 *  @category Class
 *  @package  Bdd_Videoannotator
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     TODO
 *
 */
class ServerConnector
{

    private $_server_process;
    private $_server_proc_pipes;
    private $_client;
    private $_publishAddress;
    private $_outputDirectory;
    private $_video_width;
    private $_video_height;
    //Must pipe errors to a temporary outputfile as 
    //pipe option from proc_open() does not work on windows
    const SERVER_ERRORS_FILE = "bdd_videoannotator_server_starterrors.txt";

    /**
     * Reads adpater_config.ini and starts the AnnotationServer.
     * 
     * @throws Exception - When configuration could not be read or
     *                     server could not be started
     */
    public function __construct()
    {
        $propertiesFile = dirname(__FILE__) . DIRECTORY_SEPARATOR 
            . "adapter_config.ini";
        if (! file_exists($propertiesFile)) {
            throw new ServerConnectorException("Could not find properties_file: $propertiesFile");
        }
        
        $arguments = parse_ini_file($propertiesFile);
        $this->_publishAddress = "http://localhost:" 
            . $arguments['publish_port'] . "/bdd_videoannotator";
        $this->_outputDirectory = $arguments['output_directory'];
        $this->_video_width = $arguments['video_width'];
        $this->_video_height = $arguments['video_height'];
    }

    /**
     * Starts the serverProcess.
     * 
     * @throws \Exception 
     * 
     * @return \bdd_videoannotator\stub_php\AnnotationServiceService
     */    
    public function startServer()
    {
        if ($this->_server_process) {
            return $this->getServerClient();
        }
        if (file_exists(self::SERVER_ERRORS_FILE)) {
            unlink(self::SERVER_ERRORS_FILE);
        }
        $this->_server_process = proc_open(
            $this->getStartCommandAnnotationServer(), 
            array( "2" => array("file", self::SERVER_ERRORS_FILE, "a+")), 
            $this->_server_proc_pipes, getcwd(), null, 
            array( 'bypass_shell' => true )  
        );
        register_shutdown_function(
            array( $this, 'stopServer')
        );
        if (! is_resource($this->_server_process) || ! proc_get_status($this->_server_process)) {
            throw new ServerConnectorException("Could not start ServerProcess");
        }
        
        return $this->getServerClient();
    }
    
    /**
     * Returns a singleton client to the AnnotationServer.
     *
     * @return \bdd_videoannotator\stub_php\AnnotationServiceService client
     */
    public function getServerClient()
    {
        if ($this->_client == null) {
        	for( $retries = 0; $retries < 30; $retries++){
        		
        		if(strlen($this->getServerErrors()) > 0) {
        				break;
        		}
        		try {
        			$this->_client = new \bdd_videoannotator\stub_php
        			\AnnotationServiceService($this->getWSDLLocation());
        			return $this->_client;
        		} catch (\SoapFault $e) {
        			echo "\nTrying to connect again, waiting 100 ms";
        			usleep(100000);
        		}
        	}
            throw new ServerConnectorException("Could not connect to Server: " . $this->getServerErrors()); 
       }
       return $this->_client; 
    }
    
    
    private function getServerErrors(){
    			return file_get_contents(self::SERVER_ERRORS_FILE);
    }

    /**
     * Stops the server Process.
     * 
     * @return boolean true if serverProcess was terminated successfully.
     * 
     */
    public function stopServer()
    {        
        if (!isset($this->_server_process)) {
            return true;
        }
        $this->getServerClient()->stopScenario();
        $is_terminated = proc_terminate($this->_server_process);
        proc_close($this->_server_process);
        if (file_exists(self::SERVER_ERRORS_FILE)) {
            unlink(self::SERVER_ERRORS_FILE);
        }
        unset($this->_server_process);
        unset($this->_client);
        return $is_terminated;
    }
    
    /**
     * Returns the publishingAdress.
     * 
     * @return string - Address where to publish the annotationServer.
     */
    public function getPublishingAddress()
    {
        return $this->_publishAddress;
    }
    
    /**
     * Returns the address where to find the actual WSDL-File.
     * 
     * @return string
     */    
    public function getWSDLLocation()
    {
        return $this->_publishAddress . "?wsdl";
    }
    
    /**
     * Generates the command to start the serverProcess.
     * 
     * @return string
     */
    public function getStartCommandAnnotationServer()
    {
        $cmd = "java -jar " . dirname(__DIR__) . DIRECTORY_SEPARATOR 
              . "bdd-videoannotator-server-standalone.jar ";
        $cmd .= join(
            " ", array(
            $this->_publishAddress,
            $this->_outputDirectory,
            $this->_video_width,
            $this->_video_height
            )
        );
        return $cmd;
    }
}