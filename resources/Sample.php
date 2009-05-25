<?php
/**
 * A simple sample rest resource file
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
 * @author    Aaron Zeckoski <azeckoski@gmail.com>
 * @copyright 2009 Carl Hall
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version   SVN: $Id:$
 * @link      https://www.ohloh.net/p/PhpRESTo
 * @since     inception
 */

/**
 * A simple sample rest resource class
 *
 * @category Class
 * @package  Presto
 * @author   Aaron Zeckoski <azeckoski@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link     https://www.ohloh.net/p/PhpRESTo
 * 
 * @PATH /sample
 */
class Sample extends RestResource
{
	/**
	 * This is a simple method to output hello
	 * 
     * @return the output data for this request
	 * @GET
	 * @PATH /hello
	 */
	function getHello()
    {
		return "hello";
	}

	/**
	 * This will test getting various values in the handler methods
	 * 
     * @param object $path    this should be the request path
     * @param object $method  this should be the request method
     * @param object $headers this should be the request headers
     * 
     * @return the output data for this request
	 * @GET
	 * @PATH /test
     */
	function getTest($path, $method, $headers)
    {
		$str = var_export($headers, true);
		return "TEST: $method $path: $str";
	}
}

?>