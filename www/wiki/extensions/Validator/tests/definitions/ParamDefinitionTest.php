<?php

namespace ParamProcessor\Test;

use ParamProcessor\ParamDefinition;
use ParamProcessor\IParamDefinition;
use ParamProcessor\Param;
use ParamProcessor\ParamDefinitionFactory;

/**
 * Unit test base for ParamDefinition deriving classes.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 1.0
 *
 * @ingroup ParamProcessor
 * @ingroup Test
 *
 * @group ParamProcessor
 * @group ParamDefinition
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ParamDefinitionTest extends \MediaWikiTestCase {

	/**
	 * Returns a list of arrays that hold values to test handling of.
	 * Each array holds the following unnamed elements:
	 * - value (mixed, required)
	 * - valid (boolean, required)
	 * - expected (mixed, optional)
	 *
	 * ie array( '42', true, 42 )
	 *
	 * @since 0.1
	 *
	 * @param boolean $stringlyTyped
	 *
	 * @return array
	 */
	public abstract function valueProvider( $stringlyTyped = true );

	public abstract function getType();

	public function getDefinitions() {
		$params = array();

		$params['empty'] = array();

		$params['values'] = array(
			'values' => array( 'foo', '1', '0.1', 'yes', 1, 0.1 )
		);

		return $params;
	}

	public function definitionProvider() {
		$definitions = $this->getDefinitions();

		foreach ( $definitions as &$definition ) {
			$definition['type'] = $this->getType();
		}

		return $definitions;
	}

	public function getEmptyInstance() {
		return ParamDefinitionFactory::singleton()->newDefinitionFromArray( array(
			'name' => 'empty',
			'message' => 'test-empty',
			'type' => $this->getType(),
		) );
	}

	public function instanceProvider() {
		$definitions = array();

		foreach ( $this->definitionProvider() as $name => $definition ) {
			if ( !array_key_exists( 'message', $definition ) ) {
				$definition['message'] = 'test-' . $name;
			}

			$definition['name'] = $name;
			$definitions[] = array( ParamDefinitionFactory::singleton()->newDefinitionFromArray( $definition ) );
		}

		return $definitions;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetType( IParamDefinition $definition )  {
		$this->assertEquals( $this->getType(), $definition->getType() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testValidate( IParamDefinition $definition ) {
		foreach ( array( true, false ) as $stringlyTyped ) {
			$values = $this->valueProvider( $stringlyTyped );
			$options = new \ValidatorOptions();
			$options->setRawStringInputs( $stringlyTyped );

			foreach ( $values[$definition->getName()] as $data ) {
				list( $input, $valid, ) = $data;

				$param = new Param( $definition );
				$param->setUserValue( $definition->getName(), $input, $options );
				$definitions = array();
				$param->process( $definitions, array(), $options );

				$this->assertEquals(
					$valid,
					$param->getErrors() === array(),
					'The validation process should ' . ( $valid ? '' : 'not ' ) . 'pass'
				);
			}
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testFormat( IParamDefinition $sourceDefinition ) {
		$values = $this->valueProvider();
		$options = new \ValidatorOptions();

		foreach ( $values[$sourceDefinition->getName()] as $data ) {
			$definition = clone $sourceDefinition;

			list( $input, $valid, ) = $data;

			$param = new Param( $definition );
			$param->setUserValue( $definition->getName(), $input, $options );

			if ( $valid && array_key_exists( 2, $data ) ) {
				$defs = array();
				$param->process( $defs, array(), $options );

//				if ( $data[2] !== $param->getValue() ) {
//					q($param, $options, $param->getValueParser($options),$param->getValueParser($options)->parse($param->getOriginalValue()));
//				}

				$this->assertEquals(
					$data[2],
					$param->getValue()
				);
			}
		}

		$this->assertTrue( true );
	}

	protected function validate( \IParamDefinition $definition, $testValue, $validity, \ValidatorOptions $options = null ) {
		$def = clone $definition;
		$options = $options === null ? new \ValidatorOptions() : $options;

		$param = new Param( $def );
		$param->setUserValue( $def->getName(), $testValue, $options );

		$defs = array();
		$param->process( $defs, array(), $options );

		$this->assertEquals( $validity, $param->getErrors() === array() );
	}

}