<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Console\Input;

use Symfony\Component\Console\Input\InputArgument;

use Motana\Bundle\MultiKernelBundle\Test\TestCase;
use Motana\Bundle\MultiKernelBundle\Console\Input\ConditionalArgument;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Input\ConditionalArgument
 */
class ConditionalArgumentTest extends TestCase
{
	/**
	 * @var ConditionalArgument
	 */
	protected static $argument;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($mode = InputArgument::OPTIONAL, $default = 'boot')
	{
		self::$argument = new ConditionalArgument('kernel', $mode, '', $default);
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertAttributeEquals('kernel', 'name', self::$argument);
		$this->assertAttributeEquals(InputArgument::OPTIONAL, 'mode', self::$argument);
		$this->assertAttributeEquals('', 'description', self::$argument);
		$this->assertAttributeEquals('boot', 'default', self::$argument);
	}
	
	/**
	 * @covers ::setCode()
	 */
	public function testSetCode()
	{
		self::$argument->setCode($f = function($value) {
			return strlen($value) > 1;
		});
		
		$this->assertAttributeSame($f, 'code', self::$argument);
	}
	
	/**
	 * @covers ::getCode()
	 */
	public function testGetCode()
	{
		self::$argument->setCode($f = function($value) {
			return strlen($value) > 1;
		});
		
		$this->assertSame($f, self::$argument->getCode());
	}
	
	/**
	 * @covers ::getDefault()
	 */
	public function testGetDefault()
	{
		$this->assertEquals('boot', self::$argument->getDefault());
	}
	
	/**
	 * @covers ::getDefault()
	 * @depends testGetDefault
	 */
	public function testGetRequiredDefault()
	{
		$this->setUp(InputArgument::REQUIRED);
		
		$this->assertNull(self::$argument->getDefault());
	}
	
	/**
	 * @covers ::condition()
	 * @depends testSetCode
	 * @expectedException LogicException
	 * @expectedExceptionMessage You must override the condition() method in the concrete input class or use the setCode() method.
	 */
	public function testCondition()
	{
		self::$argument->getResult('test');
	}

	/**
	 * @covers ::evaluateCondition()
	 * @depends testCondition
	 * @expectedException LogicException
	 * @expectedExceptionMessage You must override the condition() method in the concrete input class or use the setCode() method.
	 */
	public function testEvaluateConditionCallsCondition()
	{
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'test'));
	}
	
	/**
	 * @covers ::evaluateCondition()
	 * @depends testSetCode
	 */
	public function testEvaluateConditionCallsCode()
	{
		self::$argument->setCode(function($value) {
			return strlen($value) > 1;
		});
		
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'test'));
	}
	
	/**
	 * @covers ::getResult()
	 * @depends testCondition
	 * @depends testSetCode
	 */
	public function testGetResult()
	{
		self::$argument->setCode(function($value) {
			return strlen($value) > 1;
		});
			
		$this->assertTrue(self::$argument->getResult('test'));
		$this->assertTrue(self::$argument->getResult('test'));
	}
}
