<?php
// +------------------------------------------------------------------------+
// | wsdl2php                                                               |
// +------------------------------------------------------------------------+
// | Copyright (C) 2005 Knut Urdalen <knut.urdalen@gmail.com>               |
// +------------------------------------------------------------------------+
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS    |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT      |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR  |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT   |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,  |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT       |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,  |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY  |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT    |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE  |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.   |
// +------------------------------------------------------------------------+
// | This software is licensed under the LGPL license. For more information |
// | see http://wsdl2php.sf.net                                             |
// +------------------------------------------------------------------------+

ini_set('soap.wsdl_cache_enabled', 0); // disable WSDL cache

/**
 * The options parameter may contain the following elements:
 * - Individual characters (do not accept values)
 * - Characters followed by a colon (parameter requires value)
 * - Characters followed by two colons (optional value)
 */
$opts = getopt('i:n:pg:');
/**
 * -i <input wsdl file>
 * -n <base_namespace for the Generated Service>
 * -p <build pear style Namespace, default is PSR-0 if an namespace is specified>
 * -g <append to complext types part of the namespace>
 */
if(!isset($opts['i'])) {
    die("usage: wsdl2php -i <wsdl-file> -n <namespace (optional)>\n");
}

$wsdl = $opts['i'];
$namespace = false;
$pear_style = isset($opts['p']);
if(isset($opts['n'])) {
    $namespace = $opts['n'];
}
$ct_namespace = $namespace;
if(isset($opts['g'])) {
    $ct_namespace = $ct_namespace . ($pear_style ? ('_'.$opts['g']) : ('\\' . $opts['g']));
}

//$namespace is for the services
//$ct_namespace is for the complex types

//Predefined keyswords as of php 5.4
//http://www.php.net/manual/en/reserved.keywords.php
$keywords = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor');

print "Analyzing WSDL";

try {
    $client = new SoapClient($wsdl);
} catch(SoapFault $e) {
    die($e);
}
print ".";
$dom = new DOMDocument();
$dom->load($wsdl);
print ".";

// get documentation
$nodes = $dom->getElementsByTagName('documentation');
$doc = array('service' => '',
    'operations' => array());
foreach($nodes as $node) {
    if( $node->parentNode->localName == 'service' ) {
        $doc['service'] = trim($node->parentNode->nodeValue);
    } else if( $node->parentNode->localName == 'operation' ) {
        $operation = $node->parentNode->getAttribute('name');
        //$parameterOrder = $node->parentNode->getAttribute('parameterOrder');
        $doc['operations'][$operation] = trim($node->nodeValue);
    }
}
print ".";

// get targetNamespace
$targetNamespace = '';
$nodes = $dom->getElementsByTagName('definitions');
foreach($nodes as $node) {
    $targetNamespace = $node->getAttribute('targetNamespace');
}
print ".";

// declare service
$service = array('class' => $dom->getElementsByTagNameNS('*', 'service')->item(0)->getAttribute('name'),
    'wsdl' => $wsdl,
    'doc' => $doc['service'],
    'functions' => array());
print ".";

// PHP keywords - can not be used as constants, class names or function names!
$reserved_keywords = array('and', 'or', 'xor', 'as', 'break', 'case', 'cfunction', 'class', 'continue', 'declare', 'const', 'default', 'do', 'else', 'elseif', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'extends', 'for', 'foreach', 'function', 'global', 'if', 'new', 'old_function', 'static', 'switch', 'use', 'var', 'while', 'array', 'die', 'echo', 'empty', 'exit', 'include', 'include_once', 'isset', 'list', 'print', 'require', 'require_once', 'return', 'unset', '__file__', '__line__', '__function__', '__class__', 'abstract', 'private', 'public', 'protected', 'throw', 'try');

// ensure legal class name (I don't think using . and whitespaces is allowed in terms of the SOAP standard, should check this out and may throw and exception instead...)
$service['class'] = str_replace(' ', '_', $service['class']);
$service['class'] = str_replace('.', '_', $service['class']);
$service['class'] = str_replace('-', '_', $service['class']);

if(in_array(strtolower($service['class']), $reserved_keywords)) {
    $service['class'] .= 'Service';
}

// verify that the name of the service is named as a defined class
if(class_exists($service['class'])) {
    throw new Exception("Class '".$service['class']."' already exists");
}

/*if(function_exists($service['class'])) {
  throw new Exception("Class '".$service['class']."' can't be used, a function with that name already exists");
}*/

// get operations
$operations = $client->__getFunctions();
foreach($operations as $operation) {

  /*
   This is broken, need to handle
   GetAllByBGName_Response_t GetAllByBGName(string $Name)
   list(int $pcode, string $city, string $area, string $adm_center) GetByBGName(string $Name)

   finding the last '(' should be ok
   */
    //list($call, $params) = explode('(', $operation); // broken

    //if($call == 'list') { // a list is returned
    //}

  /*$call = array();
  preg_match('/^(list\(.*\)) (.*)\((.*)\)$/', $operation, $call);
  if(sizeof($call) == 3) { // found list()

  } else {
    preg_match('/^(.*) (.*)\((.*)\)$/', $operation, $call);
    if(sizeof($call) == 3) {

    }
  }*/

    $matches = array();
    if(preg_match('/^(\w[\w\d_]*) (\w[\w\d_]*)\(([\w\$\d,_ ]*)\)$/', $operation, $matches)) {
        $returns = $matches[1];
        $call = $matches[2];
        $params = $matches[3];
    } else if(preg_match('/^(list\([\w\$\d,_ ]*\)) (\w[\w\d_]*)\(([\w\$\d,_ ]*)\)$/', $operation, $matches)) {
        $returns = $matches[1];
        $call = $matches[2];
        $params = $matches[3];
    } else { // invalid function call
        throw new Exception('Invalid function call: ' . $operation);
    }

    $params = explode(', ', $params);

    $paramsArr = array();
    foreach($params as $param) {
        $paramsArr[] = explode(' ', $param);
    }
    //  $call = explode(' ', $call);
    $function = array('name' => $call,
        'method' => $call,
        'return' => $returns,
        'doc' => isset($doc['operations'][$call])?$doc['operations'][$call]:'',
        'params' => $paramsArr);

    // ensure legal function name
    if(in_array(strtolower($function['method']), $reserved_keywords)) {
        $function['name'] = '_'.$function['method'];
    }

    // ensure that the method we are adding has not the same name as the constructor
    if(strtolower($service['class']) == strtolower($function['method'])) {
        $function['name'] = '_'.$function['method'];
    }

    // ensure that there's no method that already exists with this name
    // this is most likely a Soap vs HttpGet vs HttpPost problem in WSDL
    // I assume for now that Soap is the one listed first and just skip the rest
    // this should be improved by actually verifying that it's a Soap operation that's in the WSDL file
    // QUICK FIX: just skip function if it already exists
    $add = true;
    foreach($service['functions'] as $func) {
        if($func['name'] == $function['name']) {
            $add = false;
        }
    }
    if($add) {
        $service['functions'][] = $function;
    }
    print ".";
}

$types = $client->__getTypes();

// Finds extensions.
$typesExtensions = array();
$extensionNodes = $dom->getElementsByTagName('extension');
foreach($extensionNodes as $en)
{
	// Retrieves the extending type node.
	$typeChildNode = $en->parentNode;
	while(!empty($typeChildNode) && $typeChildNode->localName !== 'complexType') $typeChildNode = $typeChildNode->parentNode;
	if(!empty($typeChildNode))
	{
		// Stores the extension for later retrieval.
		$typeChildName	= $typeChildNode->attributes->getNamedItem('name')->nodeValue;
		$typeParentName	= explode(':', $en->attributes->getNamedItem('base')->nodeValue);
		$typeParentName = end($typeParentName);
		$typesExtensions[$typeChildName] = $typeParentName;
	}
}
unset($extensionNodes, $typeChildNode, $typeChildName, $typeParentName);

$primitive_types = array('string', 'int', 'long', 'float', 'boolean', 'dateTime', 'double', 'short', 'UNKNOWN', 'base64Binary', 'decimal', 'ArrayOfInt', 'ArrayOfFloat', 'ArrayOfString', 'decimal', 'hexBinary'); // TODO: dateTime is special, maybe use PEAR::Date or similar
$service['types'] = array();
foreach($types as $type) {
    $parts = explode("\n", $type);
    $class = explode(" ", $parts[0]);
    $class = trim($class[1]);

    if( substr($class, -2, 2) == '[]' ) { // array skipping
        continue;
    }

    if( substr($class, 0, 7) == 'ArrayOf' ) { // skip 'ArrayOf*' types (from MS.NET, Axis etc.)
        continue;
    }


    $members = array();
    for($i=1; $i<count($parts)-1; $i++) {
        $parts[$i] = trim($parts[$i]);
        list($type, $member) = explode(" ", substr($parts[$i], 0, strlen($parts[$i])-1) );

        // check syntax
        if(preg_match('/^$\w[\w\d_]*$/', $member)) {
            throw new Exception('illegal syntax for member variable: '.$member);
            continue;
        }

        // IMPORTANT: Need to filter out namespace on member if presented
        if(strpos($member, ':')) { // keep the last part
            list($tmp, $member) = explode(':', $member);
        }

        // OBS: Skip member if already presented (this shouldn't happen, but I've actually seen it in a WSDL-file)
        // "It's better to be safe than sorry" (ref Morten Harket)
        $add = true;
        foreach($members as $mem) {
            if($mem['member'] == $member) {
                $add = false;
            }
        }
        if($add) {
            $members[] = array('member' => $member, 'type' => $type);
        }
    }

    // gather enumeration values
    $values = array();
    if(count($members) == 0) {
        $values = checkForEnum($dom, $class);
    }

    $full_class = $class;
    $php_class_name = $class;
    if($namespace && $pear_style){
        $full_class = $ct_namespace . $class;
        $parts = explode('_', $class);
        $php_class_name = $parts[count($parts) - 1];
    }else if($namespace){
        $full_class = suppress_keywords('\\' . $ct_namespace . '\\' . str_replace('_', '\\', $class), $keywords);
        $parts = explode('\\', $full_class);
        $php_class_name = $parts[count($parts) - 1];
    }

    $service['types'][] = array('phpClassName' => $php_class_name, 'baseClass'=> $class, 'class' => $full_class, 'members' => $members, 'values' => $values);
    print ".";
}
print "done\n";

print "Generating code...";
$code = "";

// add types
foreach($service['types'] as $type) {

    if($namespace) {
        $dirname = $ct_namespace;
        $filename = '';
        if($pear_style){
            $dirname = str_replace('_', '/', $dirname);
            $filename = $type['class'] . '.php';
        }else{
            $dirname = dirname(str_replace('\\', '/', $type['class']));
            if($dirname[0] == '/'){
                $dirname = substr($dirname, 1);
            }
            $filename = $type['phpClassName'] . '.php';
        }
        if(!is_dir($dirname))
            mkdir($dirname, 0777, true);
        $file = fopen($dirname . '/' . $filename, 'w');
    }
    //  $code .= "/**\n";
    //  $code .= " * ".(isset($type['doc'])?$type['doc']:'')."\n";
    //  $code .= " * \n";
    //  $code .= " * @package\n";
    //  $code .= " * @copyright\n";
    //  $code .= " */\n";
	
	// Extensions: look for parent.
	$parentTypeArr = null;
	if(!empty($typesExtensions[$type['baseClass']]))
		foreach($service['types'] as $t)
			if($t['baseClass'] === $typesExtensions[$type['baseClass']])
			{
				$parentTypeArr = $t;
				break;
			}
	
	// Add enumeration values.
	if($namespace && $pear_style)
	{
		$classNameForDefinition = $type['class'];
	}
	elseif($namespace)
	{
		$ns = str_replace('/', '\\', dirname(str_replace('\\', '/', $type['class'])));
		if($ns[0] == '\\') $ns = substr($ns, 1);
		$code .= "namespace $ns;\n";
		$classNameForDefinition = $type['phpClassName'];
	}
	else
	{
		$classNameForDefinition = $type['class'];
	}
	
	$code .= 'class ' . $classNameForDefinition
			.(!empty($parentTypeArr) ? ' extends '.$parentTypeArr['class'] : '')
			." {\n";
	
	unset($parentTypeArr);
	
    foreach($type['values'] as $value) {
        $code .= "\tconst ".generatePHPSymbol($value)." = '$value';\n";
    }

    // add member variables
    foreach($type['members'] as $member) {
        $code .= "\t/** ";
        if(!in_array($member['type'], $primitive_types) && $namespace) {
            $hint  = '';
            if($pear_style){
                $hint = $ct_namespace . $member['type'];
            }else{
                $hint =  '\\' . $ct_namespace . '\\' . str_replace('_', '\\', $member['type']);
            }
            if(strpos($hint, 'ArrayOf') !== FALSE){
                $hint = 'array ' . str_replace('ArrayOf', '', $hint);
            }
            $code .= '@var ' . $hint;
        } else {
			$code .= '@var ' .
					(strpos($member['type'], 'ArrayOf') === 0 // If starts with ArrayOf
					? str_replace('ArrayOf', '', $member['type']) . '[]'
					: $member['type']);
        }
        $code .= " */\tpublic \$".$member['member'] . ";\n";
    }
    $code .= "}\n\n";
    if(isset($file))
    {
        print "Writing " . $type['baseClass']. ".php...";
        fwrite($file, "<?php\n\n".$code."\n");
        fclose($file);
        $code = "";
        print "ok\n";
    }

}

// add service

// page level docblock
//$code .= "/**\n";
//$code .= " * ".$service['class']." class file\n";
//$code .= " * \n";
//$code .= " * @author    {author}\n";
//$code .= " * @copyright {copyright}\n";
//$code .= " * @package   {package}\n";
//$code .= " */\n\n";


// require types
//foreach($service['types'] as $type) {
//  $code .= "/**\n";
//  $code .= " * ".$type['class']." class\n";
//  $code .= " */\n";
//  $code .= "require_once '".$type['class'].".php';\n";
//}

if($namespace && !$pear_style){
    $code .= "namespace " . $namespace . ";\n";
}

$code .= "\n";

// class level docblock
$code .= "/**\n";
$code .= " * ".$service['class']." class\n";
$code .= " * \n";
$code .= parse_doc(" * ", $service['doc']);
$code .= " * \n";
$code .= " * @author    {author}\n";
$code .= " * @copyright {copyright}\n";
$code .= " * @package   {package}\n";
$code .= " */\n";

$code .= "class ".$service['class']." extends \\SoapClient {\n\n";

// add default wsdl
$code .= "\tconst WSDL_FILE = \"".$service['wsdl']."\";\n";

// add classmap
$code .= "\tprivate \$classmap = array(\n";
foreach($service['types'] as $type) {
	$code .= "\t\t\t'".$type['baseClass']."' => '".$type['class']."',\n";
}
$code .= "\t\t\t);\n\n";
$code .= "\tpublic function __construct(\$wsdl = null, \$options = array()) {\n";

// initialize classmap (merge)
$code .= "\t\tforeach(\$this->classmap as \$key => \$value) {\n";
$code .= "\t\t\tif(!isset(\$options['classmap'][\$key])) {\n";
$code .= "\t\t\t\t\$options['classmap'][\$key] = \$value;\n";
$code .= "\t\t\t}\n";
$code .= "\t\t}\n";
$code .= "\t\tif(isset(\$options['headers'])) {\n";
$code .= "\t\t\t\$this->__setSoapHeaders(\$options['headers']);\n";
$code .= "\t\t}\n";
$code .= "\t\tparent::__construct(\$wsdl ?: self::WSDL_FILE, \$options);\n";
$code .= "\t}\n\n";

foreach($service['functions'] as $function) {
	$code .= "\t/**\n";
	$code .= parse_doc("\t * ", $function['doc']);
	$code .= "\t *\n";

    $signature = array(); // used for function signature
    $para = array(); // just variable names
    if(count($function['params']) > 0) {
        foreach($function['params'] as $param) {
            if(count($param) == 2) {
                $typeHint = $param[0] . ' ';
                if(isTypeHint($typeHint, $primitive_types)) {
                    if($namespace && $pear_style){
                        $typeHint = $ct_namespace . $typeHint;
                    }else if($namespace){
						$typeHint = suppress_keywords('\\' . $ct_namespace . '\\' . str_replace('_', '\\', $typeHint), $keywords);
                    }
                }
                else $typeHint = '';
                $typeName = $param[1];
            }
            else {
                $typeHint = '';
                $typeName = $param[0];
            }
            $code .= "\t * @param $typeHint$typeName\n";
      /*$typehint = false;
      foreach($service['types'] as $type) {
    if($type['class'] == $param[0]) {
      $typehint = true;
    }
      }
      $signature[] = ($typehint) ? implode(' ', $param) : $param[1];*/
            $signature[] = $typeHint . $typeName;
            $para[] = $typeName;
        }
    }
    $returnHint = $function['return'];
    if(isTypeHint($function['return'], $primitive_types)) {
        if($namespace && $pear_style){
            $returnHint = $ct_namespace . $returnHint;
        }else if($namespace){
			$returnHint = suppress_keywords('\\' . $ct_namespace . '\\' . str_replace('_', '\\', $returnHint), $keywords);
        }
    }
	$code .= "\t * @return ".$returnHint."\n";
	$code .= "\t */\n";
	$code .= "\tpublic function ".$function['name']."(".implode(', ', $signature).") {\n";
	// $code .= "\t\treturn \$this->client->".$function['name']."(".implode(', ', $para).");\n";
	$code .= "\t\treturn \$this->__soapCall('".$function['method']."', array(";
    $params = array();
    if(count($signature) > 0) { // add arguments
        foreach($signature as $param) {
            if(strpos($param, ' ')) { // slice
                $tmpParam = explode(' ', $param);
                $param = array_pop($tmpParam);
                unset($tmpParam);
            }
            $params[] = $param;
        }
        //$code .= "\n      ";
        $code .= implode(", ", $params);
        //$code .= "\n      ),\n";
    }
    $code .= "), ";
    //$code .= implode(', ', $signature)."),\n";
	$code .= "array(\n";
	$code .= "\t\t\t\t\t\t'uri' => '".$targetNamespace."',\n";
	$code .= "\t\t\t\t\t\t'soapaction' => ''\n";
	$code .= "\t\t\t\t\t)\n";
	$code .= "\t\t\t);\n";
	$code .= "\t}\n\n";
}
$code .= "}\n\n";
print "done\n";

print "Writing ".$service['class'].".php...";
$filename = $service['class'].".php";
if($namespace && !$pear_style){
  $dirname = str_replace('\\', '/', $namespace);
  if(!is_dir($dirname)){
    mkdir($dirname, 0777, true);
  }
  $filename = $dirname . '/' . $filename;
}
$fp = fopen($filename, 'w');
fwrite($fp, "<?php\n".$code."\n");
fclose($fp);
print "done\n";

function suppress_keywords($txt, $keywords){
    foreach($keywords as $keyword){
        if(stristr($txt, '\\' . $keyword) !== FALSE){
            //Keyword found in class or namespace;
            $txt = str_ireplace('\\' . $keyword, '\\A' . $keyword, $txt);
        }
    }
    return $txt;
}

function parse_doc($prefix, $doc) {
    $code = "";
    $words = explode(' ', $doc);
    $line = $prefix;
    foreach($words as $word) {
        $line .= $word.' ';
        if( strlen($line) > 90 ) { // new line
            $code .= $line."\n";
            $line = $prefix;
        }
    }
    $code .= $line."\n";
    return $code;
}

/**
 * Look for enumeration
 *
 * @param DOM $dom
 * @param string $class
 * @return array
 */
function checkForEnum(&$dom, $class) {
    $values = array();

    $node = findType($dom, $class);
    if(!$node) {
        return $values;
    }

    $value_list = $node->getElementsByTagName('enumeration');
    if($value_list->length == 0) {
        return $values;
    }

    for($i=0; $i<$value_list->length; $i++) {
        $values[] = $value_list->item($i)->attributes->getNamedItem('value')->nodeValue;
    }
    return $values;
}

/**
 * Look for a type
 *
 * @param DOM $dom
 * @param string $class
 * @return DOMNode
 */
function findType(&$dom, $class) {
    $types_node  = $dom->getElementsByTagName('types')->item(0);
    $schema_list = $types_node->getElementsByTagName('schema');

    for ($i=0; $i<$schema_list->length; $i++) {
        $children = $schema_list->item($i)->childNodes;
        for ($j=0; $j<$children->length; $j++) {
            $node = $children->item($j);
            if ($node instanceof DOMElement &&
                $node->hasAttributes() &&
                $node->attributes->getNamedItem('name') &&
                $node->attributes->getNamedItem('name')->nodeValue == $class) {
                    return $node;
                }
        }
    }
    return null;
}

function generatePHPSymbol($s) {
    global $reserved_keywords;

    if(!preg_match('/^[A-Za-z_]/', $s)) {
        $s = 'value_'.$s;
    }
    if(in_array(strtolower($s), $reserved_keywords)) {
        $s = '_'.$s;
    }
    return preg_replace('/[-.\s]/', '_', $s);
}

function isTypeHint($typeHint, array $primitive_types) {
	//return $istypehint = !in_array($typeHint, $primitive_types) && !(substr($typeHint, 0, 7) == 'ArrayOf');
    //NOTICE: Ueberpruefung funktioniert wegen Whitespaces aus Zeile 465 nicht.
    //        Fix mit false, da insbesondere bei Konstanten fehlerhafte Typehints erzeugt werden
    //        die dazu führen, dass der erzeugte Client-Code nicht verwendbar ist.
    return false;
}
