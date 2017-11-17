<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Input;

use Motana\Bundle\MultikernelBundle\Console\Input\ConditionalKernelArgument;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Console\Input\InputArgument;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Input\ConditionalKernelArgument
 * @testdox Motana\Bundle\MultikernelBundle\Console\Input\ConditionalKernelArgument
 */
class ConditionalKernelArgumentTest extends TestCase
{
	/**
	 * @var ConditionalArgument
	 */
	protected static $argument;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($mode = InputArgument::OPTIONAL, $default = 'boot')
	{
		self::$argument = new ConditionalKernelArgument('kernel', $mode, '', [ 'boot', 'app' ]);
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Check the properties are initialized correctly
		$this->assertAttributeEquals('kernel', 'name', self::$argument);
		$this->assertAttributeEquals(InputArgument::OPTIONAL, 'mode', self::$argument);
		$this->assertAttributeEquals('', 'description', self::$argument);
		$this->assertAttributeEquals(null, 'default', self::$argument);
	}
	
	/**
	 * @covers ::setCode()
	 * @testdox setCode() sets the code property
	 */
	public function test_setCode()
	{
		// Set the callback for the argument
		self::$argument->setCode($f = function($value) {
			return strlen($value) > 1;
		});
		
		// Check the code property is set correctly
		$this->assertAttributeSame($f, 'code', self::$argument);
	}
	
	/**
	 * @covers ::getCode()
	 * @testdox getCode() returns the code property
	 */
	public function test_getCode()
	{
		// Set the callback for the argument
		self::$argument->setCode($f = function($value) {
			return strlen($value) > 1;
		});
		
		// Check the correct function is returned
		$this->assertSame($f, self::$argument->getCode());
	}
	
	/**
	 * @covers ::getDefault()
	 * @testdox getDefault() returns NULL for optional argument
	 */
	public function test_getDefault_with_optional_argument()
	{
		// Check the default value is NULL
		$this->assertEquals(null, self::$argument->getDefault());
	}
	
	/**
	 * @covers ::getDefault()
	 * @testdox getDefault() returns NULL for required argument
	 */
	public function test_getDefault_with_required_argument()
	{
		// Set up a required argument
		$this->setUp(InputArgument::REQUIRED);
		
		// Check the default value is NULL
		$this->assertNull(self::$argument->getDefault());
	}
	
	/**
	 * Data provider for test_condition().
	 *
	 * @return array
	 */
	public function provide_test_condition_data()
	{
		return [
			'TRUE for \'boot\'' => [
				true,
				'boot'
			],
			'TRUE for \'app\'' => [
				true,
				'app'
			],
			'FALSE for \'invalid\'' => [
				false,
				'invalid'
			],
		];
	}
	
	/**
	 * @covers ::condition()
	 * @dataProvider provide_test_condition_data
	 * @testdox condition() returns
	 */
	public function test_condition($expectedResult = null, $value = null)
	{
		// Check the returned condition result is valid
		$this->assertEquals($expectedResult, $this->callMethod(self::$argument, 'condition', $value));
	}
	
	/**
	 * Data provider for test_evaluateCondition().
	 * @return array
	 */
	public function provide_test_evaluateCondition_data()
	{
		return [
			'TRUE for \'boot\'' => [
				true,
				'boot'
			],
			'TRUE for \'app\'' => [
				true,
				'app'
			],
			'FALSE for \'invalid\'' => [
				false,
				'invalid'
			],
		];
	}
	
	/**
	 * @covers ::evaluateCondition()
	 * @dataProvider provide_test_evaluateCondition_data
	 * @testdox evaluateCondition() returns
	 */
	public function test_evaluateCondition()
	{
		// Throw an exception in the callback for the argument
		self::$argument->setCode(function(){
			throw new \LogicException('this should not happen');
		});
		
		// Check no exception is thrown
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'boot'));
	}
	
	/**
	 * @covers ::evaluateCondition()
	 * @testdox evaluateCondition() does not call code Closure
	 */
	public function test_evaluateCondition_with_Closure()
	{
		// Throw an exception in the callback for the argument
		self::$argument->setCode(function($value) {
			throw new \LogicException('this should not happen');
		});
		
		// Check no exception is thrown
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'boot'));
	}
	
	/**
	 * Data provider for test_getResult().
	 *
	 * @return array
	 */
	public function provide_test_getResult_data()
	{
		return [
			'TRUE for \'boot\'' => [
				true,
				'boot'
			],
			'TRUE for \'app\'' => [
				true,
				'app'
			],
			'FALSE for \'invalid\'' => [
				false,
				'invalid'
			],
		];
	}
	
	/**
	 * @covers ::getResult()
	 * @dataProvider provide_test_getResult_data
	 * @testdox getResult() returns
	 */
	public function test_getResult_returns_correct_results($expectedResult = null, $value = null)
	{
		// Check the expected result is returned
		$this->assertEquals($expectedResult, self::$argument->getResult($value));
		
		// Check the cached result is returned
		$this->assertEquals($expectedResult, self::$argument->getResult($value));
	}
}
