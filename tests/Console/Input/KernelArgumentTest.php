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

use Motana\Bundle\MultiKernelBundle\Console\Input\KernelArgument;
use Motana\Bundle\MultiKernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Input\KernelArgument
 */
class KernelArgumentTest extends TestCase
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
		self::$argument = new KernelArgument('kernel', $mode, '', array('boot', 'app'));
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertAttributeEquals('kernel', 'name', self::$argument);
		$this->assertAttributeEquals(InputArgument::OPTIONAL, 'mode', self::$argument);
		$this->assertAttributeEquals('', 'description', self::$argument);
		$this->assertAttributeEquals(null, 'default', self::$argument);
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
		$this->assertEquals(null, self::$argument->getDefault());
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
	 * Data provider for testCondition() and testGetResult().
	 * 
	 * @return array
	 */
	public function provide_testCondition_data()
	{
		return array(
			array(true, 'boot'),
			array(true, 'app'),
			array(false, 'invalid'),
		);
	}
	
	/**
	 * @covers ::condition()
	 * @dataProvider provide_testCondition_data
	 * @depends testSetCode
	 */
	public function testCondition($expectedResult = null, $value = null)
	{
		$this->assertEquals($expectedResult, $this->callMethod(self::$argument, 'condition', $value));
	}
	
	/**
	 * Data provider for testEvaluateConditionCallsCondition().
	 * @return array
	 */
	public function provide_testEvaluateConditionCallsCondition_data()
	{
		return array(
			array(true, 'boot'),
			array(true, 'app'),
			array(false, 'invalid'),
		);
	}
	
	/**
	 * @covers ::evaluateCondition()
	 * @dataProvider provide_testEvaluateConditionCallsCondition_data
	 * @depends testCondition
	 */
	public function testEvaluateConditionCallsCondition()
	{
		self::$argument->setCode(function(){
			throw new \LogicException('this should not happen');
		});
		
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'boot'));
	}
	
	/**
	 * @covers ::evaluateCondition()
	 * @depends testSetCode
	 */
	public function testEvaluateConditionDoesNotCallCode()
	{
		self::$argument->setCode(function($value) {
			throw new \LogicException('this should not happen');
		});
			
		$this->assertTrue($this->callMethod(self::$argument, 'evaluateCondition', 'boot'));
	}
	
	/**
	 * Data provider for testGetResult().
	 *
	 * @return array
	 */
	public function provide_testGetResult_data()
	{
		return array(
			array(true, 'boot'),
			array(true, 'app'),
			array(false, 'invalid'),
		);
	}
	
	/**
	 * @covers ::getResult()
	 * @dataProvider provide_testGetResult_data
	 */
	public function testGetResult($expectedResult = null, $value = null)
	{
		$this->assertEquals($expectedResult, self::$argument->getResult($value));
		$this->assertEquals($expectedResult, self::$argument->getResult($value));
	}
}
