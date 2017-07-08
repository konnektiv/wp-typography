<?php
/**
 *  This file is part of wp-Typography.
 *
 *  Copyright 2015-2017 Peter Putzer.
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or ( at your option ) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  @package wpTypography/Tests
 *  @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace PHP_Typography\Tests;

use \PHP_Typography\DOM;

/**
 * DOM unit test.
 *
 * @coversDefaultClass \PHP_Typography\DOM
 * @usesDefaultClass \PHP_Typography\DOM
 */
class DOM_Test extends PHP_Typography_Testcase {

	/**
	 * HTML parser.
	 *
	 * @var \Masterminds\HTML5
	 */
	private $parser;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine
		$this->parser = new \Masterminds\HTML5( [
			'disable_html_ns' => true,
		] );
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() { // @codingStandardsIgnoreLine
	}

	/**
	 * Load given HTML document.
	 *
	 * @param  string $html An HTML document.
	 *
	 * @return \DOMDocument
	 */
	protected function load_html( $html ) {
		return $this->parser->loadHTML( $html );
	}

	/**
	 * Test block_tags.
	 *
	 * @covers ::block_tags
	 */
	public function test_block_tags() {
		$block_tags = DOM::block_tags( true );
		$this->assertInternalType( 'array', $block_tags );

		$tag_names = array_keys( $block_tags );
		$this->assertContainsOnly( 'string', $tag_names );

		// Our "custom" block tags.
		$this->assertContains( 'dt', $tag_names );
		$this->assertContains( 'li', $tag_names );
		$this->assertContains( 'td', $tag_names );

		// Some block tags native to HTML5-PHP.
		$this->assertContains( 'p', $tag_names );
		$this->assertContains( 'div', $tag_names );

		// Non-block tags.
		$this->assertNotContains( 'img', $tag_names );
	}

	/**
	 * Test nodelist_to_array.
	 *
	 * @covers ::nodelist_to_array
	 */
	public function test_nodelist_to_array() {
		$parser = new \Masterminds\HTML5( [
			'disable_html_ns' => true,
		] );
		$dom = $parser->loadHTML( '<body><p>blabla</p><ul><li>foo</li><li>bar</li></ul></body>' );
		$xpath = new \DOMXPath( $dom );

		$node_list = $xpath->query( '//*' );
		$node_array = DOM::nodelist_to_array( $node_list );

		$this->assertGreaterThan( 1, $node_list->length );
		$this->assertSame( $node_list->length, count( $node_array ) );
		foreach ( $node_list as $node ) {
			$this->assertArrayHasKey( spl_object_hash( $node ), $node_array );
			$this->assertSame( $node, $node_array[ spl_object_hash( $node ) ] );
		}
	}

	/**
	 * Provide data for testing get_ancestors.
	 *
	 * @return array
	 */
	public function provide_get_ancestors_data() {
		return [
			[ '<div class="ancestor"><p class="ancestor">bar <span id="origin">foo</span></p></div><p>foo <span>bar</span></p>', '//*[@id="origin"]' ],
		];
	}

	/**
	 * Test get_ancestors.
	 *
	 * @covers ::get_ancestors
	 *
	 * @uses ::nodelist_to_array
	 *
	 * @dataProvider provide_get_ancestors_data
	 *
	 * @param  string $html        HTML input.
	 * @param  string $xpath_query XPath query.
	 */
	public function test_get_ancestors( $html, $xpath_query ) {
		$parser = new \Masterminds\HTML5( [
			'disable_html_ns' => true,
		] );
		$dom = $parser->loadHTML( '<body>' . $html . '</body>' );
		$xpath = new \DOMXPath( $dom );

		$origin = $xpath->query( $xpath_query )->item( 0 );
		$ancestor_array = DOM::get_ancestors( $origin );
		$ancestor_array_xpath = DOM::nodelist_to_array( $xpath->query( 'ancestor::*', $origin ) );

		$this->assertSame( count( $ancestor_array ), count( $ancestor_array_xpath ) );
		foreach ( $ancestor_array as $ancestor ) {
			$this->assertContains( $ancestor, $ancestor_array_xpath );
		}
	}

	/**
	 * Provide data for testing has_class.
	 *
	 * @return array
	 */
	public function provide_has_class_data() {
		return [
			[ '<span class="foo bar"></span>', '//span', 'bar', true ],
			[ '<span class="foo bar"></span>', '//span', 'foo', true ],
			[ '<span class="foo bar"></span>', '//span', 'foobar', false ],
			[ '<span class="foo bar"></span>', '//span', [ 'foo' ], true ],
			[ '<span class="foo bar"></span>', '//span', [ 'foo', 'bar' ], true ],
			[ '<span class="foo bar"></span>', '//span', '', false ],
			[ '<span class="foo bar"></span>', '//span', [], false ],
			[ '<span class="foo bar"></span>', '//span', [ '' ], false ],
			[ '<span class="foo bar">something</span>', '//span/text()', 'bar', true ],
			[ '<span>something</span>', '//span', 'foo', false ],
		];
	}

	/**
	 * Test has_class.
	 *
	 * @covers ::has_class
	 *
	 * @dataProvider provide_has_class_data
	 *
	 * @param  string $html        HTML input.
	 * @param  string $xpath_query XPath query.
	 * @param  array  $classnames  Array of classnames.
	 * @param  bool   $result      Expected result.
	 */
	public function test_has_class( $html, $xpath_query, $classnames, $result ) {
		$parser = new \Masterminds\HTML5( [
			'disable_html_ns' => true,
		] );
		$dom = $parser->loadHTML( '<body>' . $html . '</body>' );
		$xpath = new \DOMXPath( $dom );

		$nodes = $xpath->query( $xpath_query );
		foreach ( $nodes as $node ) {
			$this->assertSame( $result, DOM::has_class( $node, $classnames ) );
		}
	}

	/**
	 * Test get_block_parent.
	 *
	 * @covers ::get_block_parent
	 */
	public function test_get_block_parent() {
		$html = '<div id="outer"><p id="para"><span>A</span><span id="foo">new hope.</span></p><span><span id="bar">blabla</span></span></div>';
		$doc = $this->load_html( $html );
		$xpath = new \DOMXPath( $doc );

		$outer_div  = $xpath->query( "//*[@id='outer']" )->item( 0 ); // really only one.
		$paragraph  = $xpath->query( "//*[@id='para']" )->item( 0 );  // really only one.
		$span_foo   = $xpath->query( "//*[@id='foo']" )->item( 0 );   // really only one.
		$span_bar   = $xpath->query( "//*[@id='bar']" )->item( 0 );   // really only one.
		$textnode_a = $xpath->query( "//*[@id='para']//text()" )->item( 0 ); // we don't care which one.
		$textnode_b = $xpath->query( "//*[@id='bar']//text()" )->item( 0 );  // we don't care which one.
		$textnode_c = $xpath->query( "//*[@id='foo']//text()" )->item( 0 );  // we don't care which one.

		$this->assertSame( $paragraph, DOM::get_block_parent( $span_foo ) );
		$this->assertSame( $paragraph, DOM::get_block_parent( $textnode_a ) );
		$this->assertSame( $outer_div, DOM::get_block_parent( $paragraph ) );
		$this->assertSame( $outer_div, DOM::get_block_parent( $span_bar ) );
		$this->assertSame( $outer_div, DOM::get_block_parent( $textnode_b ) );
		$this->assertSame( $paragraph, DOM::get_block_parent( $textnode_c ) );
	}


		/**
		 * Test get_prev_chr.
		 *
		 * @covers ::get_prev_chr
		 * @covers ::get_previous_textnode
		 * @covers ::get_adjacent_textnode
		 *
		 * @uses ::get_last_textnode
		 * @uses ::get_edge_textnode
		 * @uses PHP_Typography\Strings::functions
		 */
		public function test_get_prev_chr() {
			$html = '<p><span>A</span><span id="foo">new hope.</span></p><p><span id="bar">The empire</span> strikes back.</p<';
			$doc = $this->load_html( $html );
			$xpath = new \DOMXPath( $doc );

			$textnodes = $xpath->query( "//*[@id='foo']/text()" ); // really only one.
			$prev_char = DOM::get_prev_chr( $textnodes->item( 0 ) );
			$this->assertSame( 'A', $prev_char );

			$textnodes = $xpath->query( "//*[@id='bar']/text()" ); // really only one.
			$prev_char = DOM::get_prev_chr( $textnodes->item( 0 ) );
			$this->assertSame( '', $prev_char );
		}

		/**
		 * Test get_previous_textnode.
		 *
		 * @covers ::get_previous_textnode
		 * @covers ::get_adjacent_textnode
		 */
		public function test_get_previous_textnode_null() {
			$node = DOM::get_previous_textnode( null );
			$this->assertNull( $node );
		}

		/**
		 * Test get_next_chr.
		 *
		 * @covers ::get_next_chr
		 * @covers ::get_next_textnode
		 * @covers ::get_adjacent_textnode
		 *
		 * @uses ::get_first_textnode
		 * @uses ::get_edge_textnode
		 * @uses PHP_Typography\Strings::functions
		 */
		public function test_get_next_chr() {
			$html = '<p><span id="foo">A</span><span id="bar">new hope.</span></p><p><span>The empire</span> strikes back.</p<';
			$doc = $this->load_html( $html );
			$xpath = new \DOMXPath( $doc );

			$textnodes = $xpath->query( "//*[@id='foo']/text()" ); // really only one.
			$prev_char = DOM::get_next_chr( $textnodes->item( 0 ) );
			$this->assertSame( 'n', $prev_char );

			$textnodes = $xpath->query( "//*[@id='bar']/text()" ); // really only one.
			$prev_char = DOM::get_next_chr( $textnodes->item( 0 ) );
			$this->assertSame( '', $prev_char );
		}

		/**
		 * Test get_next_textnode.
		 *
		 * @covers ::get_next_textnode
		 * @covers ::get_adjacent_textnode
		 */
		public function test_get_next_textnode_null() {
			$node = DOM::get_next_textnode( null );
			$this->assertNull( $node );
		}


		/**
		 * Test get_first_textnode.
		 *
		 * @covers ::get_first_textnode
		 * @covers ::get_edge_textnode
		 */
		public function test_get_first_textnode() {
			$html = '<p><span id="foo">A</span><span id="bar">new hope.</span></p>';
			$doc = $this->load_html( $html );
			$xpath = new \DOMXPath( $doc );

			$textnodes = $xpath->query( "//*[@id='foo']/text()" ); // really only one.
			$node = DOM::get_first_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'A', $node->nodeValue );

			$textnodes = $xpath->query( "//*[@id='foo']" ); // really only one.
			$node = DOM::get_first_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'A', $node->nodeValue );

			$textnodes = $xpath->query( "//*[@id='bar']" ); // really only one.
			$node = DOM::get_first_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'new hope.', $node->nodeValue );

			$textnodes = $xpath->query( '//p' ); // really only one.
			$node = DOM::get_first_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'A', $node->nodeValue );
		}

		/**
		 * Test get_first_textnode.
		 *
		 * @covers ::get_first_textnode
		 * @covers ::get_edge_textnode
		 */
		public function test_get_first_textnode_null() {
			// Passing null returns null.
			$this->assertNull( DOM::get_first_textnode( null ) );

			// Passing a DOMNode that is not a DOMElement or a DOMText returns null as well.
			$this->assertNull( DOM::get_first_textnode( new \DOMDocument() ) );
		}

		/**
		 * Test get_first_textnode.
		 *
		 * @covers ::get_first_textnode
		 * @covers ::get_edge_textnode
		 */
		public function test_get_first_textnode_only_block_level() {
			$html = '<div><div id="foo">No</div><div id="bar">hope</div></div>';
			$doc = $this->load_html( $html );
			$xpath = new \DOMXPath( $doc );

			$textnodes = $xpath->query( '//div' ); // really only one.
			$node = DOM::get_first_textnode( $textnodes->item( 0 ) );
			$this->assertNull( $node );
		}

		/**
		 * Test get_last_textnode.
		 *
		 * @covers ::get_last_textnode
		 * @covers ::get_edge_textnode
		 *
		 * @uses ::get_first_textnode
		 */
		public function test_get_last_textnode() {

			$html = '<p><span id="foo">A</span><span id="bar">new hope.</span> Really.</p>';
			$doc = $this->load_html( $html );
			$xpath = new \DOMXPath( $doc );

			$textnodes = $xpath->query( "//*[@id='foo']/text()" ); // really only one.
			$node = DOM::get_last_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'A', $node->nodeValue );

			$textnodes = $xpath->query( "//*[@id='foo']" ); // really only one.
			$node = DOM::get_last_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'A', $node->nodeValue );

			$textnodes = $xpath->query( "//*[@id='bar']" ); // really only one.
			$node = DOM::get_first_textnode( $textnodes->item( 0 ) );
			$this->assertSame( 'new hope.', $node->nodeValue );

			$textnodes = $xpath->query( '//p' ); // really only one.
			$node = DOM::get_last_textnode( $textnodes->item( 0 ) );
			$this->assertSame( ' Really.', $node->nodeValue );
		}

		/**
		 * Test get_last_textnode.
		 *
		 * @covers ::get_last_textnode
		 * @covers ::get_edge_textnode
		 */
		public function test_get_last_textnode_null() {
			// Passing null returns null.
			$this->assertNull( DOM::get_last_textnode( null ) );

			// Passing a DOMNode that is not a DOMElement or a DOMText returns null as well.
			$this->assertNull( DOM::get_last_textnode( new \DOMDocument() ) );
		}


		/**
		 * Test get_last_textnode.
		 *
		 * @covers ::get_last_textnode
		 * @covers ::get_edge_textnode
		 */
		public function test_get_last_textnode_only_block_level() {
			$html = '<div><div id="foo">No</div><div id="bar">hope</div></div>';
			$doc = $this->load_html( $html );
			$xpath = new \DOMXPath( $doc );

			$textnodes = $xpath->query( '//div' ); // really only one.
			$node = DOM::get_last_textnode( $textnodes->item( 0 ) );
			$this->assertNull( $node );
		}
}
