<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Input;

use Motana\Bundle\MultikernelBundle\Console\Input\ConditionalArgument;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Console\Input\InputArgument;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Input\ConditionalArgument
 * @testdox Motana\Bundle\MultikernelBundle\Console\Input\ConditionalArgument
 */
class ConditionalArgumentTest extends TestCase
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
		self::$argument = new ConditionalArgument('kernel', $mode, '', $default);
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
		$this->assertAttributeEquals('boot', 'default', self::$argument);
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
		
		// Check the correct callback is returned
		$this->assertSame($f, self::$argument->getCode());
	}
	
	/**
	 * @covers ::getDefault()
	 * @testdox getDefault() returns the default value
	 */
	public function test_getDefault()
	{
		// Check the correct default value is returned
		$this->assertEquals('boot', self::$argument->getDefault());
	}
	
	/**
	 * @covers ::getDefault()
	 * @testdox getDefault() returns NULL for required arguments
	 */
	public function test_getDefault_returns_NULL()
	{
		// Set up a required argument
		$this->setUp(InputArgument::REQUIRED);
		
		// Check the default value is NULL
		$this->assertNull(self::$argument->getDefault());
	}
	
	/**
	 * @covers ::condition()
	 * @expectedException LogicException
	 * @expectedExceptionMessage You must override the condition() method in the concrete input class or use the setCode() method.
	 * @testdox condition() throws a LogicException
	 */
	public function test_condition()
	{
		// Check an exception is thrown when the callback is not set
		self::$argument->getResult('test');
	}

	/**
	 * @covers ::evaluateCondition()
	 * @expectedException LogicException
	 * @expectedExceptionMessage You must override the condition() method in the concrete input class or use the setCode() method.
	 * @testdox evaluateCondition() throws a LogicException
	 */
	public function test_evaluateCondition()
	{
		// Check an exception is thrown when the callback is not set
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'test'));
	}
	
	/**
	 * @covers ::evaluateCondition()
	 * @testdox evaluateCondition() calls the code Closure
	 */
	public function test_evaluateCondition_calls_Closure()
	{
		// Set the callback for the argument
		self::$argument->setCode(function($value) {
			return strlen($value) > 1;
		});
		
		// Check the callback returns the correct result
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'test'));
	}
	
	/**
	 * @covers ::getResult()
	 * @testdox getResult() returns the return value of the Closure
	 */
	public function test_getResult()
	{
		// Set the callback for the argument
		self::$argument->setCode(function($value) {
			return strlen($value) > 1;
		});
			
		// Check the correct result is returned for matching values
		$this->assertTrue(self::$argument->getResult('test'));
		
		// Check the cached result is returned
		$this->assertTrue(self::$argument->getResult('test'));
		
		// Check the correct result is returned for non-matching values
		$this->assertFalse(self::$argument->getResult('t'));
		
		// Check the cached result is returned
		$this->assertFalse(self::$argument->getResult('t'));
	}
}
