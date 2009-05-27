<?php
/**
 * Tests for annotation parsing.
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
 * @copyright 2009 Carl Hall
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link      https://www.ohloh.net/p/PhpRESTo
 */

require_once 'simpletest/autorun.php';
require_once '../Presto.php';

/**
 * Test class for annotations.
 *
 * @category Class
 * @package  Presto
 * @author   Carl Hall <carl.hall@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link     https://www.ohloh.net/p/PhpRESTo
 */
class AnnotationsTest extends UnitTestCase
{
    private $_presto;

    /**
     * Setup common resources for tests.
     * 
     * @return void
     */
    function setUp()
    {
        $_presto = new RestController();
    }

    /**
     * Test parsing of doc comments on a class.
     * 
     * @return void
     */
    function testClassTagParsing()
    {
        echo 'yay, test';
    }
}
?>
