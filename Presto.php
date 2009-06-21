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
    /**
     * Default constructor
     *
     * @return
     */
    function __construct()
    {
        //loadResources();
    }

    /**
     * Loads resources found on disc.
     *
     * @return void
     */
    function loadResources()
    {
        $resources_dir = @opendir('resources')
            || die('Resources directory not found.');
        while ($file = readdir($resources_dir)) {
            if ($file != '.' && $file != '..') {
                include $file;
            }
        }
    }

    /**
     * Display the results of dispatching the current URL.
     *
     * @return void
     */
    function display()
    {
        echo $this->dispatch();
    }

    /**
     * Dispatch the current URL to the appropriate resource and return the results.
     *
     * @param String $url The URL to use for dispatching.
     * 
     * @return Results from the matched resource.
     */
    function dispatch($url = null)
    {
        $entity = null;
        $id = null;

        if (empty($url)) {
            // get everything after the name of the script
            //$url = substr($_SERVER['REQUEST_URI'],
            //  strlen($_SERVER['SCRIPT_NAME']));\
            $url = $_SERVER['REQUEST_URI'];
        }

        // separate the uri into elements by splitting on /
        $elements = explode('/', $url);

        // make sure there is at least 1 element to work with (ie. a resource name)
        $num_elements = count($elements);
        $resource = null;
        if ($num_elements >= 1) {
            $entity = urldecode($elements[0]);
            require self::RESOURCE_DIR.$entity;
            if (class_exists($entity)) {
                $resource = new $entity;
            }
        }

        if ($num_elements >= 2) {
            $id = $this->getId(urldecode($elements[1]));
        }

        // get the request method and output format
        $method = $this->getMethod();
        $format = $this->getFormat($url);

        $results = null;
        $data = $_POST['data'];

        switch ($method) {
        // read
        case 'GET':
            if (!empty($id)) {
                if ($id == 'new') {
                    $resource->_new();
                } else {
                    // match on @GET /resource/{?}, @GET /{?}
                    $resource->read($id);
                }
            } else {
                // get a list of the current user's data
                $resource->index();
            }
            break;

        // update
        case 'POST':
            $resource->create($data);
            break;

        // create
        case 'PUT':
            $resource->update($data);
            break;

        // delete
        case 'DELETE':
            $resource->delete($id);
            break;
        }

        $results = null;
        if (!empty($resource->output)) {
            $results = $this->transform($resource->output, $format);
        }
        header(
            "HTTP/1.1 {$resource->response_code}", true, $resource->response_code
        );
        
        /*
        if ($results === true) {
            send_response_code(204);
        } elseif ($results === false) {
            send_response_code(400);
        } elseif (is_numeric($results)) {
            send_response_code($results);
        } elseif ($results != null) {
            $output = $this->transform($results, $format);
            return $output;
        }
        */
    }

    /**
     * Gets the request method performed.
     * 
     * @return The request method.  REQUEST_METHOD is used. If the method is GET or
     *         POST, _method is checked on the request for override.
     */
    protected function getMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if (($method == 'GET' || $method == 'POST') && !empty($_GET['_method'])) {
            $method = strtoupper($_GET['_method']);
        }
        return $method;
    }

    /**
     * Get the requested response format based on a URL element.
     * 
     * @param String $name Resource name to check for response format.
     * 
     * @return Format found on the name (String after last '.').  Default is 'json'.
     */
    protected function getFormat($name)
    {
        // set the default format
        //$format = 'html';
        $format = 'json';

        $last_slash = strrpos($name, '/');
        $last_dot = strrpos($name, '.');

        if ($last_slash === false) {
            $last_slash = -1;
        }

        if ($last_dot !== false && $last_dot < strlen($name) - 1
            && $last_dot > $last_slash
        ) {
            $format = substr($name, $last_dot + 1);
        }

        return $format;
    }

    /**
     * Get the ID from a URL element.
     * 
     * @param String $name URL element to check for ID.
     * 
     * @return ID found in the URL element (String before last '.').
     */
    protected function getId($name)
    {
        $id = '';
        $last_slash = strrpos($name, '/');
        $last_dot = strrpos($name, '.');

        if ($last_slash === false) {
            $last_slash = -1;
        }

        if ($last_dot === false && $last_slash === false) {
            // neither dot nor slash was found
            $id = $name;
        } elseif ($last_dot !== false && $last_dot > $last_slash) {
            // a dot was found after a slash
            $id = substr($name, $last_slash + 1, $last_dot);
        } else {
            $id = substr($name, $last_slash + 1);
        }

        return $id;
    }

    /**
     * Factory method to transform json into a different format.
     * 
     * @param String $results The results to transform.
     * @param String $format  The format to transform to.
     * 
     * @return The results transformed to the request format.
     */
    function transform($results, $format)
    {
        // initialize templating
        include_once SMARTY_DIR . 'Smarty.class.php';
        $smarty = new Smarty;
        $smarty->assign('title', $results['title']);

        $data = $results['output'];

        $output = '';
        // send back the array if no format or 'raw'
        if (empty($format) || $format == 'raw') {
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
     * Parses the annotations found on methods in a class.
     *
     * @param Class $class Class instance to check methods for annotations.
     * 
     * @return Associative array of annotations on methods.
     */
    protected function getMethodsAnnotations($class)
    {
        $annotations = array();

        // get the methods of the class
        $refClass = new ReflectionClass($class);
        $methods = $refClass->getMethods();

        foreach ( $methods as $method ) {
            $methodAnnotations = $this->getAnnotationsFromText(
                $method->getDocComment()
            );
            $annotations[$method->getName()] = $methodAnnotations;
        }
       
        return $annotations;
    }

    /**
     * Parses the annotations from a block of text usually taken from a class
     * or method doc comment.
     *
     * @param String $text Text to check for annotations.
     * 
     * @return Associative array of annotations found in text.
     */
    protected function getAnnotationsFromText($text)
    {
        // split on @ then push the first element off because it is not part of
        // the annotation.
        $annotations = explode('@', $text);
        array_shift($annotations);
        $annotations = array_map('trim', $annotations);
        return $annotations;
    }
}

/*
 * Resource base class.
 *
class RestResource
{
    protected $response_code = 204;
    protected $headers = array();
    protected $output = null;

    /**
     *  these are only placeholders.  these will be replaced by annotation based
     *  calls.
     *
    function _new()
    {
    }
    function _list()
    {
    }
    function create()
    {
    }
    function read()
    {
    }
    function update()
    {
    }
    function delete()
    {
    }
}
*/
?>
