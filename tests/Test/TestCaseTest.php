<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Test;

use Symfony\Component\Filesystem\Filesystem;

use Motana\Bundle\MultiKernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Test\TestCase
 */
class TestCaseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var TestCase
	 */
	protected static $testCase;
	
	/**
	 * @var null
	 */
	public static $testProperty_public_static;
	
	/**
	 * @var null
	 */
	public $testProperty_public;
	
	/**
	 * @var null
	 */
	private static $testProperty_private_static;
	
	/**
	 * @var null
	 */
	private $testProperty_private;
	
	/**
	 * Dummy method for testCallMethod() tests.
	 */
	private function dummyMethod()
	{
		
	}
	
	/**
	 * Dummy method for testCallStaticMethod() tests.
	 */
	private static function dummyMethod_static()
	{
		
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$testCase = $this->getMockForAbstractClass(TestCase::class);
		self::$testProperty_public_static = null;
		self::$testProperty_private_static = null;
		$this->testProperty_public = null;
		$this->testProperty_private = null;
	}
	
	/**
	 * @covers ::callMethod()
	 */
	public function testCallMethod()
	{
		self::$testCase->callMethod($this, 'dummyMethod');
	}
	
	/**
	 * @covers ::callMethod()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cannot invoke static method "dummyMethod_static".
	 */
	public function testCallMethodException()
	{
		self::$testCase->callMethod($this, 'dummyMethod_static');
	}
	
	/**
	 * @covers ::callMethod()
	 */
	public function testCallMethodStatic()
	{
		self::$testCase->callMethod(self::class, 'dummyMethod_static');
	}
	
	/**
	 * @covers ::callMethod()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cannot invoke non-static method "dummyMethod".
	 */
	public function testCallMethodStaticException()
	{
		self::$testCase->callMethod(self::class, 'dummyMethod');
	}
	
	/**
	 * @covers ::callStaticMethod()
	 */
	public function testCallStaticMethod()
	{
		self::$testCase->callStaticMethod(self::class, 'dummyMethod_static');
	}
	
	/**
	 * @covers ::callStaticMethod()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cannot invoke non-static method "dummyMethod".
	 */
	public function testCallStaticMethodException()
	{
		self::$testCase->callStaticMethod(self::class, 'dummyMethod');
	}
	
	/**
	 * @covers ::getFs()
	 */
	public function testGetFs()
	{
		$oldFs = self::$testCase->getFs();
		
		$attribute = new \ReflectionProperty(self::$testCase, 'fs');
		$attribute->setAccessible(true);
		$attribute->setValue(null, null);
		$attribute->setAccessible(false);
		
		// Check that getFs() returns an instance of the correct class
		$this->assertInstanceOf(Filesystem::class, self::$testCase->getFs());
		
		// Check that getFs() created a new Filesystem instance
		$this->assertNotSame($oldFs, self::$testCase->getFs());
		
		// Check that the fs property is initialized with an instance of the correct class
		$this->assertInstanceOf(Filesystem::class, $this->getStaticAttribute(TestCase::class, 'fs'));
	}

	/**
	 * @covers ::setObjectAttribute()
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #1 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setObjectAttribute() must be a object
	 */
	public function testSetObjectAttributeChecksObjectType()
	{
		self::$testCase->setObjectAttribute(null, false, false);
	}
	
	/**
	 * @covers ::setObjectAttribute()
	 * @depends testSetObjectAttributeChecksObjectType
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #2 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setObjectAttribute() must be a string
	 */
	public function testSetObjectAttributeChecksAttributeNameType()
	{
		self::$testCase->setObjectAttribute($this, false, false);
	}
	
	/**
	 * @covers ::setObjectAttribute()
	 * @depends testSetObjectAttributeChecksAttributeNameType
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #2 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setObjectAttribute() must be a valid attribute name
	 */
	public function testSetObjectAttributeChecksAttributeNameIsValid()
	{
		self::$testCase->setObjectAttribute($this, '0attribute', false);
	}
	
	/**
	 * @covers ::setObjectAttribute()
	 * @depends testSetObjectAttributeChecksAttributeNameIsValid
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Attribute "invalid" not found in object.
	 */
	public function testSetObjectAttributeChecksAttributeExists()
	{
		self::$testCase->setObjectAttribute($this, 'invalid', false);
	}
	
	/**
	 * @covers ::setObjectAttribute()
	 * @depends testSetObjectAttributeChecksAttributeExists
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Cannot access static attribute "testProperty_private_static" as non-static.
	 */
	public function testSetObjectAttributeChecksAttributeIsNotStatic()
	{
		self::$testCase->setObjectAttribute($this, 'testProperty_private_static', false);
	}

	/**
	 * @covers ::setObjectAttribute()
	 * @depends testSetObjectAttributeChecksAttributeExists
	 */
	public function testSetObjectAttributePublic()
	{
		self::$testCase->setObjectAttribute($this, 'testProperty_public', 1);
		
		$this->assertEquals(1, $this->testProperty_public);
	}

	/**
	 * @covers ::setObjectAttribute()
	 * @depends testSetObjectAttributePublic
	 */
	public function testSetObjectAttributePrivate()
	{
		self::$testCase->setObjectAttribute($this, 'testProperty_private', 1);
		
		$this->assertEquals(1, $this->testProperty_private);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #1 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setStaticAttribute() must be a string
	 */
	public function testSetStaticAttributeChecksClassNameType()
	{
		self::$testCase->setStaticAttribute(false, false, false);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributeChecksClassNameType
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #1 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setStaticAttribute() must be a class name
	 */
	public function testSetStaticAttributeChecksClassExists()
	{
		self::$testCase->setStaticAttribute('invalid', false, false);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributeChecksClassExists
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #2 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setStaticAttribute() must be a string
	 */
	public function testSetStaticAttributeChecksAttributeNameType()
	{
		self::$testCase->setStaticAttribute(self::class, false, false);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributeChecksClassExists
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #2 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::setStaticAttribute() must be a valid attribute name
	 */
	public function testSetStaticAttributeChecksAttributeNameIsValid()
	{
		self::$testCase->setStaticAttribute(self::class, '0attribute', false);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributeChecksAttributeNameType
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Attribute "invalid" not found in object.
	 */
	public function testSetStaticAttributeChecksAttributeExists()
	{
		self::$testCase->setStaticAttribute(self::class, 'invalid', null);
	}

	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributeChecksAttributeExists
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Cannot access non-static attribute "testProperty_private" as static.
	 */
	public function testSetStaticAttributeChecksAttributeIsStatic()
	{
		self::$testCase->setStaticAttribute(self::class, 'testProperty_private', null);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributeChecksAttributeIsStatic
	 */
	public function testSetStaticAttributePublic()
	{
		self::$testCase->setStaticAttribute(self::class, 'testProperty_public_static', 1);
		
		$this->assertEquals(1, self::$testProperty_public_static);
	}
	
	/**
	 * @covers ::setStaticAttribute()
	 * @depends testSetStaticAttributePublic
	 */
	public function testSetStaticAttributePrivate()
	{
		self::$testCase->setStaticAttribute(self::class, 'testProperty_private_static', 1);
		
		$this->assertEquals(1, self::$testProperty_private_static);
	}
	
	/**
	 * @covers ::writeAttribute()
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #2 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::writeAttribute() must be a string
	 */
	public function testWriteAttributeChecksAttributeNameType()
	{
		self::$testCase->writeAttribute(false, false, false);
	}
	
	/**
	 * @covers ::writeAttribute()
	 * @depends testWriteAttributeChecksAttributeNameType
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #2 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::writeAttribute() must be a valid attribute name
	 */
	public function testWriteAttributeChecksAttributeIsValid()
	{
		self::$testCase->writeAttribute(false, '0attribute', false);
	}
	
	/**
	 * @covers ::writeAttribute()
	 * @depends testWriteAttributeChecksAttributeIsValid
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #1 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::writeAttribute() must be a class name or object
	 */
	public function testWriteAttributeChecksClassOrObjectType()
	{
		self::$testCase->writeAttribute(false, 'testProperty_private', false);
	}
	
	/**
	 * @covers ::writeAttribute()
	 * @depends testWriteAttributeChecksClassOrObjectType
	 * @expectedException PHPUnit_Framework_Exception
	 * @expectedExceptionMessage Argument #1 (No Value) of Motana\Bundle\MultiKernelBundle\Test\TestCase::writeAttribute() must be a class name
	 */
	public function testWriteAttributeStaticChecksClassExists()
	{
		self::$testCase->writeAttribute('invalid', 'testProperty_private', false);
	}
	
	/**
	 * @covers ::writeAttribute()
	 * @depends testWriteAttributeStaticChecksClassExists
	 */
	public function testWriteAttributeStatic()
	{
		self::$testCase->writeAttribute(self::class, 'testProperty_private_static', 1);
		
		$this->assertEquals(1, self::$testProperty_private_static);
	}
	
	/**
	 * @covers ::writeAttribute()
	 * @depends testWriteAttributeStatic
	 */
	public function testWriteAttributeObject()
	{
		self::$testCase->writeAttribute($this, 'testProperty_private', 1);
		
		$this->assertEquals(1, $this->testProperty_private);
	}
}
