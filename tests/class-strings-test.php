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

use PHP_Typography\Strings;

/**
 * DOM unit test.
 *
 * @coversDefaultClass \PHP_Typography\Strings
 * @usesDefaultClass \PHP_Typography\Strings
 */
class Strings_Test extends \PHPUnit\Framework\TestCase {

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() { // @codingStandardsIgnoreLine
	}

	/**
	 * Reports an error identified by $message if the given function array contains a non-callable.
	 *
	 * @param array  $func    An array of string functions.
	 * @param string $message Optional. Default ''.
	 */
	protected function assertStringFunctions( array $func, $message = '' ) {
		// Each function is a callable (except for the 'u' modifier string).
		foreach ( $func as $name => $function ) {
			if ( 'u' !== $name ) {
				$this->assertTrue( is_callable( $function ) );
			}
		}
	}

	/**
	 * Test ::functions.
	 *
	 * @covers ::functions
	 */
	public function test_functions() {
		$func_ascii = Strings::functions( 'ASCII' );
		$func_utf8  = Strings::functions( 'UTF-8 üäß' );

		// We are dealing with ararys.
		$this->assertTrue( is_array( $func_ascii ) );
		$this->assertTrue( is_array( $func_utf8 ) );

		// The arrays are not (almost) empty.
		$this->assertGreaterThan( 1, count( $func_ascii ), 'ASCII array contains fewer than 2 functions.' );
		$this->assertGreaterThan( 1, count( $func_utf8 ),  'UTF-8 array contains fewer than 2 functions.' );

		// The keys are identical.
		$this->assertSame( array_keys( $func_ascii ), array_keys( $func_utf8 ) );

		// Each function is a callable (except for the 'u' modifier string).
		$this->assertStringFunctions( $func_ascii );
		$this->assertStringFunctions( $func_utf8 );
	}

	/**
	 * Provide data for testing uchr.
	 *
	 * @return array
	 */
	public function provide_uchr_data() {
		return [
			[ 33,   '!' ],
			[ 9,    "\t" ],
			[ 10,   "\n" ],
			[ 35,   '#' ],
			[ 103,  'g' ],
			[ 336,  'Ő' ],
			[ 497,  'Ǳ' ],
			[ 1137, 'ѱ' ],
			[ 2000, 'ߐ' ],
		];
	}

	/**
	 * Test uchr.
	 *
	 * @covers ::uchr
	 * @dataProvider provide_uchr_data
	 *
	 * @param  int    $code   Character code.
	 * @param  string $result Expected result.
	 */
	public function test_uchr( $code, $result ) {
		$this->assertSame( $result, Strings::uchr( $code ) );
	}

	/**
	 * Provide data for testing mb_str_split.
	 *
	 * @return array
	 */
	public function provide_mb_str_split_data() {
		return [
			[ '', 1, 'UTF-8', [] ],
			[ 'A ship', 1, 'UTF-8', [ 'A', ' ', 's', 'h', 'i', 'p' ] ],
			[ 'Äöüß', 1, 'UTF-8', [ 'Ä', 'ö', 'ü', 'ß' ] ],
			[ 'Äöüß', 2, 'UTF-8', [ 'Äö', 'üß' ] ],
			[ 'Äöüß', 0, 'UTF-8', false ],
		];
	}

	/**
	 * Test mb_str_split.
	 *
	 * @covers ::mb_str_split
	 * @dataProvider provide_mb_str_split_data
	 *
	 * @param  string $string   A multibyte string.
	 * @param  int    $length   Split length.
	 * @param  string $encoding Encoding to use.
	 * @param  array  $result   Expected result.
	 */
	public function test_mb_str_split( $string, $length, $encoding, $result ) {
		$this->assertSame( $result, Strings::mb_str_split( $string, $length, $encoding ) );
	}
}
