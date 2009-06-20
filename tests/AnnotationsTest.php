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

require_once 'PHPUnit/Framework.php';
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
class AnnotationsTest extends PHPUnit_Framework_TestCase
{
    private $_presto;

    /**
     * Setup common resources for tests.
     * 
     * @return void
     */
    function setUp()
    {
        $this->_presto = new RestController();
    }

    /**
     * Test parsing of simple annotation (ie. no text after annotation).
     *
     * @return void
     */
    function testSimpleTag()
    {
        $text = "/**\n"
            . " * This is some test text that is\n"
            . " * contained in a multiline comment.\n"
            . " *\n"
            . " * @GET\n"
            . " */";
        $annotations = $this->_presto->getAnnotationsFromText($text);
        $this->assertTrue(array_key_exists('GET', $annotations));
    }

    /**
     * Test parsing of simple annotation (ie. no text after annotation).
     *
     * @return void
     */
    function testSimpleTags()
    {
        $text = "/**\n"
            . " * This is some test text that is\n"
            . " * contained in a multiline comment.\n"
            . " *\n"
            . " * @GET\n"
            . " * @Cool\n"
            . " * @sweet\n"
            . " */";
        $annotations = $this->_presto->getAnnotationsFromText($text);
        $this->assertTrue(array_key_exists('GET', $annotations));
        $this->assertEquals($annotations['GET'], null);

        $this->assertTrue(array_key_exists('Cool', $annotations));
        $this->assertEquals($annotations['Cool'], null);

        $this->assertTrue(array_key_exists('sweet', $annotations));
        $this->assertEquals($annotations['sweet'], null);
    }

    /**
     * Test parsing of simple annotation (ie. no text after annotation).
     *
     * @return void
     */
    function testTagWithText()
    {
        $text = "/**\n"
            . " * This is some test text that is\n"
            . " * contained in a multiline comment.\n"
            . " *\n"
            . " * @GET /some/url\n"
            . " */";
        $annotations = $this->_presto->getAnnotationsFromText($text);
        $this->assertTrue(array_key_exists('GET', $annotations));
        $this->assertEquals($annotations['GET'], '/some/url');
    }

    /**
     * Test parsing of simple annotation (ie. no text after annotation).
     *
     * @return void
     */
    function testTagsWithText()
    {
        $text = "/**\n"
            . " * This is some test text that is\n"
            . " * contained in a multiline comment.\n"
            . " *\n"
            . " * @GET /some/url\n"
            . " * @Property some kind of thing\n"
            . " * @thingy this has some odd text\n"
            . " */";
        $annotations = $this->_presto->getAnnotationsFromText($text);
        $this->assertTrue(array_key_exists('GET', $annotations));
        $this->assertEquals($annotations['GET'], '/some/url');

        $this->assertTrue(array_key_exists('Property', $annotations));
        $this->assertEquals($annotations['Property'], 'some kind of thing');

        $this->assertTrue(array_key_exists('thingy', $annotations));
        $this->assertEquals($annotations['thingy'], 'this has some odd text');
    }

    function testParamTag()
    {
        
    }

    function testParamTags()
    {
        
    }

    function testSameTagRepeated()
    {
        
    }
}
?>
