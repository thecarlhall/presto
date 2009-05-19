<?php
require_once 'http_response.php';

/**
 * Base class for handling REST based calls.
 *
 * URLs should be handled in the convention specified in the REST microformat.
 * http://microformats.org/wiki/rest/urls
 */
class RestController
{
    function __construct()
    {

    }

    function loadResources()
    {
        $resources_dir = @opendir('resources') or die('Resources directory not found.');
        while ($file = readdir($resources_dir)) {
            if ($file != '.' && $file != '..') {
                require $file;
            }
        }
    }

    function display()
    {
        echo $this->dispatch();
    }

    function dispatch($url)
    {
    	$entity = null;
        $id = null;

//        if (empty($url)) {
//            // get everything after the name of this script
//            $url = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
//        }

        // separate the uri into elements split on /
        $elements = explode('/', $url);

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

        $format = !empty($_GET['_format']) ? strtolower($_GET['_format']): $this->get_format($url);
        $method = !empty($_GET['_method']) ? strtoupper($_GET['_method']): $_SERVER['REQUEST_METHOD'];

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
        send_response_code($resource->response_code, $results);

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
     * Get the requested response format based on the name of the requested
     * playlist.
     */
    protected function get_format($name)
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
            && $last_dot > $last_slash) {
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
        require_once SMARTY_DIR . 'Smarty.class.php';
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

    function get_class_annotations($class) {
        $refClass = new ReflectionClass($class);
        $comment = $refClass->getDocComment();

        $annotations = explode('@', $comment);
        array_shift($annotations);
    }

    function get_methods_annotations($class) {

    }
}

interface RestResource
{
	protected $response_code = 204;
	protected $headers = array();
    protected $output = null;

    // these are only placeholders.  these will be replaced by annotation based
	// calls.
    function _new();
    function _list();
    function create();
    function read();
    function update();
    function delete();
}
?>
