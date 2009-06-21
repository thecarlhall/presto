<?php
/**
    Copyright 2009, Carl Hall <carl.hall@gmail.com>

    PHP version 5

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

       

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.

    @category
    @package
    @author
    @license Apache 2.0, http://www.apache.org/licenses/LICENSE-2.0
    @link
*/
/**
 * Base class for handling REST based calls.
 *
 * URLs should be handled in the convention specified in the REST microformat.
 * http://microformats.org/wiki/rest/urls
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
        loadResources();
    }

    function loadResources()
    {
        $resources_dir = @opendir('resources')
            or die('Resources directory not found.');
        while ($file = readdir($resources_dir)) {
            if ($file != '.' && $file != '..') {
                include $file;
            }
        }
    }

    function display()
    {
        echo $this->dispatch();
    }

    function dispatch($url)
    {
        !empty($url) or die('Cannot dispatch without a URL.');

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
        $resource = null;
        if ($num_elements >= 1) {
            $entity = urldecode($elements[0]);
            $resource = new $entity;
        }

        if ($num_elements >= 2) {
            $id = $this->get_id(urldecode($elements[1]));
        }

        /** @TODO only check for _method when REQUEST_METHOD = (GET|POST) */
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
     */
    protected function getMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == 'GET' or $method == 'POST') {
            if (!empty($_GET['_method'])) {
                $method = strtoupper($_GET['_method']);
            }
        }
        return $method;
    }

    /**
     * Get the requested response format based on the name of the requested
     * playlist.
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

    protected function get_id($name)
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
            $id = substr($name, $last_slash + 1, $last_dot);
        } else {
            $id = substr($name, $last_slash + 1);
        }

        return $id;
    }

    /**
     * Factory method to transform json into a different format.
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
        if (empty($format) or $format == 'raw') {
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

    protected function get_class_annotations($class)
    {
        // using reflection, get the doc comment for parsing
        $refClass = new ReflectionClass($class);
        $comment = $refClass->getDocComment();

        $this->get_annotations_from_text($comment);

        return $annotations;
    }

    /**
     * Parses the annotations found on methods in a class.
     *
     * @param $class Class instance.
     */
    protected function getMethodsAnnotations($class)
    {
        $annotations = array();

        // get the methods of the class
        $refClass = new ReflectionClass($class);
        $methods = $refClass->getMethods();

        foreach ( $methods as $method ) {
            $methodAnnotations = $this->get_annotations_from_text(
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
     * @param string $text
     */
    protected function get_annotations_from_text($text)
    {
        // split on @ then push the first element off because it is not part of
        // the annotation.
        $annotations = explode('@', $text);
        array_shift($annotations);
        $annotations = array_map('trim', $annotations);
        return $annotations;
    }
}

class RestResource
{
    protected $response_code = 204;
    protected $headers = array();
    protected $output = null;

    /**
     *  these are only placeholders.  these will be replaced by annotation based
     *  calls.
     */
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
?>
