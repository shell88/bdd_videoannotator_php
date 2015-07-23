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
 *  @link     https://github.com/shell88/bdd_videoannotator
 *
 */

namespace bdd_videoannotator\bddadapters;


/**
 *  Reads config from adapter_config.ini and starts the annotationServer.
 *
 *  PHP version 5
 *
 *  @category Class
 *  @package  Bdd_Videoannotator
 *  @author   Stefan Hell <stefan.hell88@gmail.com>
 *  @license  The Apache License, Version 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 *  @link     https://github.com/shell88/bdd_videoannotator
 *
 */

class ServerConnectorException extends \RuntimeException
{
    
    /**
     * (non-PHPdoc)
     * 
     * @see RuntimeException::__toString()
     * 
     * @return nothing
     */
    public function __toString() 
    {
        return __CLASS__ . " {$this->message}\n";
    }
    
}