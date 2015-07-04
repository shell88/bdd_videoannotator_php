<?php
namespace bdd_videoannotator\stub_php;

/**
 * AnnotationServiceService class
 * 
 *  
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class AnnotationServiceService extends \SoapClient {

	const WSDL_FILE = "tmp/META-INF/wsdl/AnnotationServiceService.wsdl";
	private $classmap = array(
			'stepResult' => '\bdd_videoannotator\stub_php\stepResult',
			'stringArray' => '\bdd_videoannotator\stub_php\stringArray',
			'stringArrayArray' => '\bdd_videoannotator\stub_php\stringArrayArray',
			);

	public function __construct($wsdl = null, $options = array()) {
		foreach($this->classmap as $key => $value) {
			if(!isset($options['classmap'][$key])) {
				$options['classmap'][$key] = $value;
			}
		}
		if(isset($options['headers'])) {
			$this->__setSoapHeaders($options['headers']);
		}
		parent::__construct($wsdl ?: self::WSDL_FILE, $options);
	}

	/**
	 *  
	 *
	 * @param $scenarioName
	 * @return void
	 */
	public function startScenario($scenarioName) {
		return $this->__soapCall('startScenario', array($scenarioName), array(
						'uri' => 'http://service.bddvideoannotator.shell88.github.com/',
						'soapaction' => ''
					)
			);
	}

	/**
	 *  
	 *
	 * @param 
	 * @return void
	 */
	public function stopScenario() {
		return $this->__soapCall('stopScenario', array(), array(
						'uri' => 'http://service.bddvideoannotator.shell88.github.com/',
						'soapaction' => ''
					)
			);
	}

	/**
	 *  
	 *
	 * @param $steptext
	 * @param $datatable
	 * @return void
	 */
	public function addStepToBuffer($steptext, $datatable) {
		return $this->__soapCall('addStepToBuffer', array($steptext, $datatable), array(
						'uri' => 'http://service.bddvideoannotator.shell88.github.com/',
						'soapaction' => ''
					)
			);
	}

	/**
	 *  
	 *
	 * @param $path
	 * @return void
	 */
	public function changeOutputDirectory($path) {
		return $this->__soapCall('changeOutputDirectory', array($path), array(
						'uri' => 'http://service.bddvideoannotator.shell88.github.com/',
						'soapaction' => ''
					)
			);
	}

	/**
	 *  
	 *
	 * @param $result
	 * @return void
	 */
	public function addResultToBufferStep($result) {
		return $this->__soapCall('addResultToBufferStep', array($result), array(
						'uri' => 'http://service.bddvideoannotator.shell88.github.com/',
						'soapaction' => ''
					)
			);
	}

	/**
	 *  
	 *
	 * @param $steptext
	 * @param $datatable
	 * @param $result
	 * @return void
	 */
	public function addStepWithResult($steptext, $datatable, $result) {
		return $this->__soapCall('addStepWithResult', array($steptext, $datatable, $result), array(
						'uri' => 'http://service.bddvideoannotator.shell88.github.com/',
						'soapaction' => ''
					)
			);
	}

}


