<?php
/**
 * Presto - a lightweight REST framework for PHP
 *
 * Presto is a simple to use and very lightweight REST framework for PHP,
 * it will help you to handle rest routing and input/output of data without
 * getting in your way
 *
 * PHP version 5
 *
 * LICENSE:
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category  File
 * @package   Presto
 * @author    Carl Hall <carl.hall@gmail.com>
 * @author    Aaron Zeckoski <azeckoski@gmail.com>
 * @copyright 2009 Carl Hall
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version   SVN: $Id:$
 * @link      https://www.ohloh.net/p/PhpRESTo
 * @since     inception
 */
define('ABSPATH', dirname(__FILE__).'/'); // the absolute path to this file
error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE); // strict error reporting

// handling sample testing
$SAMPLE = isset ($_REQUEST['sample'])?true:false;

/**
 * Base class for handling REST based calls (routing and processing),
 * This will also take care of detection of REST resource classes and execution
 * of the methods,
 * URLs should be handled in the convention specified in the REST microformat.
 * http://microformats.org/wiki/rest/urls
 *
 * @category Class
 * @package  Presto
 * @author   Carl Hall <carl.hall@gmail.com>
 * @author   Aaron Zeckoski <azeckoski@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link     https://www.ohloh.net/p/PhpRESTo
 */
class RestController
{

    // {{{ constants
    const DEFAULT_RESOURCES_DIR = 'resources';

    const ANNOTATION_PATH = 'PATH';
    const ANNOTATION_METHOD = 'METHOD';
    const ANNOTATION_GET = 'GET';
    const ANNOTATION_POST = 'POST';
    const ANNOTATION_PUT = 'PUT';
    const ANNOTATION_DELETE = 'DELETE';
    // }}}

    // {{{ properties
    /**
     * The full set of all known rest classes
     * class name -> object
     * @var array
     */
    private $_restClasses = array();
    /**
     * Holds the complete set of all known Rest Resources
     * @var array
     */
    private $_restResources = array();
    /**
     * Enable debugging output (default is false which means disabled)
     * @var boolean
     */
    private $_DEBUG = false;
    // }}}

    /**
     * Create a Presto RestController
     * Can set the debugging mode here or in the 
     *
     * @param object  $resourcesPath [optional] the path to the directory with
     *     the RestResource classes, defaults to resources
     * @param boolean $debug         [optional] enable debugging output (set true),
     *     off by default (set false)
     */
    function __construct($resourcesPath = self::DEFAULT_RESOURCES_DIR, $debug = null)
    {
        if (! isset($debug)) {
            $debug = isset ($_REQUEST['debug'])?true:false;
        }
        $this->_DEBUG = $debug;
        $this->loadResources($resourcesPath);
    }

    /**
     * Causes the rest controllers in the resources directory to be loaded
     *
     * @param string $resourcesPath [optional] the path to the directory with
     *     the RestResource classes, defaults to resources
     *
     * @return void
     */
    function loadResources($resourcesPath = self::DEFAULT_RESOURCES_DIR)
    {
        // check if dir starts with / and put in ABSPATH if not
        $dirStart = substr($resourcesPath, 0, 1);
        if ($dirStart != '/') {
            // relative to the location of this file
            $resourcesPath = ABSPATH.$resourcesPath;
        }
        // open the folder and look for Rest Resource classes in the php files
        $resources_dir = @opendir($resourcesPath) or
        die ('ERROR: Resources directory '.$resourcesPath.' not found, '
        .'create a resources directory to hold your REST controllers');
        while ($file = readdir($resources_dir)) {
            // filter out all files except .php files
            $start = substr($file, 0, 1);
            if ($start != '.') {
                $pos = strrpos($file, '.');
                if ($pos > 0) {
                    $extension = substr($file, $pos);
                    if ($extension = 'php') {
                        $filePath = $resourcesPath.'/'.$file;
                        if (file_exists($filePath)) {
                            $fpos = strpos($file, '.');
                            $className = substr($file, 0, $fpos);
                            include $filePath;
                            if (class_exists($className)) {
                                echo "found file: $filePath ($className) <br/>";
                                $newClass = new $className;
                                $this->_restClasses[$className] = $newClass;
                            }
                        }
                    }
                }
            }
        }
        if (sizeof($this->_restClasses) == 0) {
            die ("WARNING: No RestResource classes found, "
            ."you need to create at least one class which extends "
            ."RestResource in: $resourcesPath");
        }
        // now we pull the resources out of all the rest classes we found
        foreach ($this->_restClasses as $class=>$obj) {
            echo "class: $class <br/>";
            $classAnnotes = $this->getClassAnnotations($class);
            if ($this->_DEBUG) {
                var_dump($classAnnotes);
            }
            echo "<br/>";
            // default convention is the name of the class
            $base_path = strtolower($class);
            if (! empty($classAnnotes[self::ANNOTATION_PATH])) {
                $base_path = $classAnnotes[self::ANNOTATION_PATH];
            }
            if (substr($base_path, 0, 1) != '/') {
                $base_path = '/'.$base_path;
            }
            $methodsAnnotes = $this->getMethodsAnnotations($class);
            var_dump($methodsAnnotes); // @TODO REMOVE LATER
            echo "<br/>";
            foreach ($methodsAnnotes as $method=>$annotes) {
                // default convention is the name of the method
                $res_path = strtolower($method);
                if (! empty($annotes[self::ANNOTATION_PATH])) {
                    $res_path = $annotes[self::ANNOTATION_PATH];
                }
                if (substr($res_path, 0, 1) != '/') {
                    $res_path = '/'.$res_path;
                }
                $res_path = $base_path.$res_path;
                $found = false;
                if (array_key_exists(self::ANNOTATION_GET, $annotes)) {
                    $path = $annotes[self::ANNOTATION_GET];
                    $resourcesPath[$method] = 'GET'.' '.$path;
                     // @TODO store in the resources array
                    $found = true;
                }
                if (array_key_exists(self::ANNOTATION_POST, $annotes)) {
                     // @TODO store in the resources array
                    $found = true;
                }
                if (array_key_exists(self::ANNOTATION_PUT, $annotes)) {
                     // @TODO store in the resources array
                    $found = true;
                }
                if (array_key_exists(self::ANNOTATION_DELETE, $annotes)) {
                     // @TODO store in the resources array
                    $found = true;
                }
                if (array_key_exists(self::ANNOTATION_METHOD, $annotes)) {
                     // @TODO store in the resources array
                    $found = true;
                }
                if (!$found) {
                    // no annotation so default to GET
                    // @TODO store in the resources array
                }
            }
        }
        // dump them
        var_dump($this->_restResources);
    }

    /**
     * This method will output the current known routes
     * and information about the presto routing system,
     * to use this just call the method and dump the
     * output into the body of an html page,
     * example: echo RestController->displayResources();
     *
     * @return HTML indicating the status of the presto routing and system
     */
    function displayResources()
    {
        // capture the output
        ob_start();
        echo "<div>";
        echo "HELLO"; // @TODO make this actually output real information
        echo "</div>";
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * Handles the actual URL dispatching by correctly calling the method
     * attached to the current URL route,
     * this will handle the output parsing and set up the status correctly,
     * it also assigns headers and dumps the output to the response stream
     *
     * @param object $url the url path
     *     (should be the path after the name of the script)
     *
     * @return void
     */
    function dispatch($url)
    {
        ! empty($url) or die ('Cannot dispatch without a URL.');

        $entity = null;
        $id = null;

        //if (empty($url)) {
        //    // get everything after the name of this script
        //    $url = substr($_SERVER['REQUEST_URI'],
        //        strlen($_SERVER['SCRIPT_NAME']));
        //}

        // separate the uri into elements split on /
        $elements = explode('/', $url);
        $num_elements = count($elements);

        // @TODO most of this is not working -AZ
        $resource = new RestResource(); // null;
        if ($num_elements >= 1) {
            $entity = urldecode($elements[0]);
            $resource = new $entity;
        }

        if ($num_elements >= 2) {
            $id = $this->getId(urldecode($elements[1]));
        }

        $method = $this->getMethod();
        $format = $this->getFormat($url);

        $results = null;
        // @TODO this should be the data from the request body instead
        $data = $_POST['data'];

        switch($method) {
        case "GET":
            // read
            if (! empty($id)) {
                if ($id == 'new') {
                    $results = $resource->httpNew();
                } else {
                    // match on @GET /resource/{?}
                    $results = $resource->httpRead($id);
                }
            } else {
                // match @GET /resource
                $results = $resource->httpList();
            }
            break;

        case "POST":
            // create (technically not always)
            $results = $resource->httpCreate($data);
            break;

        case "PUT":
            // update (idempotent create or update)
            $results = $resource->httpUpdate($data);
            break;

        case "DELETE":
            // delete
            $results = $resource->httpDelete($id);
            break;

        default:
            die ("Invalid method type specified: ".$method);
        }

        // @TODO handle the other headers

        // handle the response code
        header(
            "HTTP/1.1 {".$resource->responseCode."}", 
            true, 
            $resource->responseCode
        );

        // @TODO handle the case where the data was sent out in some other way
        $output = null;
        if (! empty($results)) {
            if (is_array($results)) {
                $output = $this->transcode($results, $format);
            } else if (is_string($results)) {
                // not so sure about this one... -AZ
                if ($resource->isJSON) {
                    $output = $this->jsonTransform($results, $format);
                }
                $output = $results;
            }
            echo $results;
        }
    }

    /**
     * Gets the request method performed from the current request (capitalized)
     *
     * @return the http method (example: GET)
     */
    protected function getMethod()
    {
        // @TODO only check for _method when REQUEST_METHOD = (GET|POST)
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == 'GET' or $method == 'POST') {
            if (! empty($_GET['_method'])) {
                $method = strtoupper($_GET['_method']);
            }
        }
        return $method;
    }

    /**
     * Get the requested response format based on the current request
     * @return
     * @param object $name
     */
     /**
     * Get the format of the output based on the request
     *
     * @param object $name the path
     *
     * @return a format based on the request
     */
    protected function getFormat($name)
    {
        // @TODO this should look at the accepts header also

        // set the default format
        //$format = 'html';
        $format = 'json';

        $last_slash = strrpos($name, '/');
        $last_dot = strrpos($name, '.');

        if ($last_slash === false) {
            $last_slash = -1;
        }

        if ($last_dot !== false 
            && $last_dot < strlen($name)-1
            && $last_dot > $last_slash
        ) {
            $format = substr($name, $last_dot+1);
        }

        return $format;
    }

    /**
     * Extract the id from a request path
     *
     * @param object $name the path
     *
     * @return the id
     * @deprecated handle this by pulling out the variable based on the path
     */
    protected function getId($name)
    {
        $id = '';
        $last_slash = strrpos($name, '/');
        $last_dot = strrpos($name, '.');

        if ($last_slash === false) {
            $last_slash = -1;
        }

        // neither dot nor slash was found
        if ($last_dot === false && $last_slash === false) {
            $id = $name;
            // a dot was found after a slash
        } elseif ($last_dot !== false && $last_dot > $last_slash) {
            $id = substr($name, $last_slash+1, $last_dot);
        } else {
            $id = substr($name, $last_slash+1);
        }

        return $id;
    }

    /**
     * This method will be used to encode an array into string output data
     *
     * @param array  $result an array of data to encode into a string
     * @param string $format [optional] defaults to json
     *
     * @return the data encoded into a string
     */
    function transcode($result, $format = "json")
    {
        // @TODO make this actually work
        return implode($result);
    }

    /**
     * Factory method to transform json into a different format
     *
     * @param object $results the output string from the methods
     * @param object $format  the reformatted output
     *
     * @return the data in the requested format
     * @deprecated what is this supposed to be doing?
     */
    function jsonTransform($results, $format)
    {
        // initialize templating
        include_once SMARTY_DIR.'Smarty.class.php';
        $smarty = new Smarty;
        $smarty->assign('title', $results['title']);

        $data = $results['output'];

        $output = '';
        // send back the array if no format or 'raw'
        if ( empty($format) or $format == 'raw') {
            $output = $data;
        } elseif ($format == 'json') {
            $output = json_encode($data);
        } else {
            $template = "{$format}.tpl";
            if (is_readable("{$smarty->template_dir}/$template")) {
                $smarty->assign('data', $data);
                $output = $smarty->fetch($template);
            } else {
                $output = "Unable to read template [$template]";
            }
        }
        return $output;
    }

    /**
     * Parses the annotations on a class into an array
     *
     * @param object $class the name of any visible class
     *
     * @return the array of annotation name -> value
     */
    protected function getClassAnnotations($class)
    {
        // using reflection, get the doc comment for parsing
        $refClass = new ReflectionClass($class);
        $comment = $refClass->getDocComment();
        $annotations = $this->getAnnotationsFromText($comment);
        return $annotations;
    }

    /**
     * Parses the annotations found on methods in a class into an array
     *
     * @param string $class the name of any visible class
     *
     * @return an array of method name -> (annotation name -> value)
     */
    protected function getMethodsAnnotations($class)
    {
        $annotations = array ();

        // get the methods of the class
        $refClass = new ReflectionClass($class);
        $methods = $refClass->getMethods();

        foreach ($methods as $method) {
            $comment = $method->getDocComment();
            $methodAnnotations = $this->getAnnotationsFromText($comment);
            $annotations[$method->getName()] = $methodAnnotations;
        }

        return $annotations;
    }

    /**
     * Parses the annotations from a block of text usually taken from a class
     * or method doc comment.
     *
     * @param string $text any doccomment text
     *
     * @return an array of annotation name -> value
     */
    protected function getAnnotationsFromText($text)
    {
        $annotations = array ();

        // strip out the comment bits in a horrible way
        $text = str_replace("/*", "", $text);
        $text = str_replace("*/", "", $text);
        $text = str_replace("*", "", $text);

        // split on @ then push the first element off because it is not part of
        // the annotation.
        $annotes = explode('@', $text);
        array_shift($annotes);
        $annotes = array_map('trim', $annotes);
        // now extract the annotations
        foreach ($annotes as $value) {
            $pos = strpos($value, " ");
            if ($pos <= 0) {
                // only an annotation
                $annotations[$value] = "";
            } else {
                // includes args
                $annote = substr($value, 0, $pos);
                if ($annote == 'param') {
                    /* to make it so we get all params back we
                     * need to do some extra handling here to 
                     * use the param name intead of the word param
                     */
                    $startpos = strpos($value, "$", $pos);
                    $pos = strpos($value, " ", $startpos);
                    if ($pos <= 0) {
                        continue; // invalid param comment
                    }
                    $annote = substr($value, $startpos, $pos-$startpos);
                }
                $annotations[$annote] = trim(substr($value, $pos+1));
            }
        }
        return $annotations;
    }

    
    /**
     * Returns the debug setting
     * 
     * @return true if debugging is enabled, false otherwise
     * @see RestController::$_DEBUG
     */
    public function getDebug()
    {
        return $this->_DEBUG;
    }
    
    /**
     * Sets the debug setting
     * 
     * @param boolean $debug set this to true to enable debug output
     * 
     * @return void
     * @see RestController::$_DEBUG
     */
    public function setDebug($debug)
    {
        $this->_DEBUG = $debug;
    }

}

/**
 * Extend this class to define a resource or resources which can be
 * accessed within rest space of your application,
 * key annotations are @PATH for the class and 
 * method annotations include @PATH, @GET, @POST, etc., 
 * see the help documents at the link below for more
 *
 * @category Class
 * @package  Presto
 * @author   Aaron Zeckoski <azeckoski@gmail.com>
 * @author   Carl Hall <carl.hall@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link     https://www.ohloh.net/p/PhpRESTo
 */
class RestResource
{
    // {{{ properties
    /**
     * This should be the http response code
     * @var int
     */
    public $responseCode = 204;
    /**
     * This put all the headers you want placed in the page in this method
     * @var array
     */
    public $headers = array ();
    /**
     * Set this to true if the string data you are returniung will be JSON format
     * @var boolean
     */
    public $isJSON = false;
    // }}}

    // these are only placeholders and will be replaced by annotation based calls.

    /**
     * placeholder
     *
     * @return the html content for the new page
     */
    public function httpNew()
    {
        return null;
    }
    /**
     * placeholder
     *
     * @return the list of entity as a data or string
     */
    public function httpList()
    {
        return null;
    }
    /**
     * placeholder
     *
     * @return the id or url of the new entity
     */
    public function httpCreate()
    {
        return null;
    }
    /**
     * placeholder
     *
     * @return the entity data as an array or a string
     */
    public function httpRead()
    {
        return null;
    }
    /**
     * placeholder
     *
     * @return true if entity updated, false if not
     */
    public function httpUpdate()
    {
        return false;
    }
    /**
     * placeholder
     *
     * @return true if entity deleted, false if not
     */
    public function httpDelete()
    {
        return false;
    }
}

// This demonstrates sample usage of the framework
if ($SAMPLE) {
    // create the RestController
    $rc = new RestController();
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
        .'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n';
    echo '<html xmlns="http://www.w3.org/1999/xhtml">\n';
    echo '    <head>\n';
    echo '        <title>Presto Sample</title>\n';
    echo '    </head>\n';
    echo '    <body>\n';
    echo '        This is the sample Presto start page<br/>\n';
    echo $rc->displayResources();
    echo '    </body>\n';
    echo '</html>\n';
}
?>
