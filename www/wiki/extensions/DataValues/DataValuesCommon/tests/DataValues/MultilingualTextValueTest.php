<?php

namespace DataValues\Tests;

use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;

/**
 * @covers DataValues\MultilingualTextValue
 *
 * @file
 * @since 0.1
 *
 * @ingroup DataValue
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MultilingualTextTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\MultilingualTextValue';
	}

	public function validConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array( array() );
		$argLists[] = array( array( new MonolingualTextValue( 'en', 'foo' ) ) );
		$argLists[] = array( array( new MonolingualTextValue( 'en', 'foo' ), new MonolingualTextValue( 'de', 'foo' ) ) );
		$argLists[] = array( array( new MonolingualTextValue( 'en', 'foo' ), new MonolingualTextValue( 'de', 'bar' ) ) );
		$argLists[] = array( array(
			new MonolingualTextValue( 'en', 'foo' ),
			new MonolingualTextValue( 'de', ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' )
		) );

		return $argLists;
	}

	public function invalidConstructorArgumentsProvider() {
		$argLists = array();

		$argLists[] = array( 42 );
		$argLists[] = array( false );
		$argLists[] = array( true );
		$argLists[] = array( null );
		$argLists[] = array( 'foo' );
		$argLists[] = array( 'en' );
		$argLists[] = array( 'en', 42 );
		$argLists[] = array( 'en', false );
		$argLists[] = array( 'en', array() );
		$argLists[] = array( 'en', null );
		$argLists[] = array( '', 'foo' );
		$argLists[] = array( 'en', 'foo' );
		$argLists[] = array( 'en', ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' );
		$argLists[] = array( new MonolingualTextValue( 'en', 'foo' ) );

		$argLists[] = array( array( 'foo' ) );
		$argLists[] = array( array( 42 => 'foo' ) );
		$argLists[] = array( array( '' => 'foo' ) );
		$argLists[] = array( array( 'en' => 42 ) );
		$argLists[] = array( array( 'en' => null ) );
		$argLists[] = array( array( 'en' => true ) );
		$argLists[] = array( array( 'en' => array() ) );
		$argLists[] = array( array( 'en' => 4.2 ) );

		$argLists[] = array( array( new MonolingualTextValue( 'en', 'foo' ), false ) );
		$argLists[] = array( array( new MonolingualTextValue( 'en', 'foo' ), 'foobar' ) );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param MultilingualTextValue $texts
	 * @param array $arguments
	 */
	public function testGetTexts( MultilingualTextValue $texts, array $arguments ) {
		$actual = $texts->getTexts();

		$this->assertInternalType( 'array', $actual );

		/**
		 * @var MonolingualTextValue $monolingualValue
		 */
		foreach ( $actual as $monolingualValue ) {
			$this->assertInstanceOf( '\DataValues\MonolingualTextValue', $monolingualValue );
		}

		$this->assertEquals( $arguments[0], array_values( $actual ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param MultilingualTextValue $texts
	 * @param array $arguments
	 */
	public function testGetValue( MultilingualTextValue $texts, array $arguments ) {
		$this->assertInstanceOf( $this->getClass(), $texts->getValue() );
	}

}
