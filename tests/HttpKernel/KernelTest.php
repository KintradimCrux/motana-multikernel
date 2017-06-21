<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\HttpKernel;

use Motana\Bundle\MultikernelBundle\Test\KernelTestCase;
use Motana\Component\HttpKernel\Kernel;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\Kernel
 */
class KernelTest extends KernelTestCase
{
	/**
	 * Data provider for testConstructor().
	 *
	 * @return array
	 */
	public function provide_testConstructor_data()
	{
		return array(
			array(null, 'cacheDir'),
			array(null, 'logDir'),
			array(array(), 'bundles'),
			array(null, 'bundleMap'),
			array(null, 'container'),
			array(dirname(dirname(__DIR__)) . '/fixtures/kernels/working/apps/app', 'rootDir'),
			array('test', 'environment'),
			array(false, 'debug'),
			array(false, 'booted'),
			array('app', 'name'),
			array(null, 'loadClassCache'),
		);
	}
	
	/**
	 * @covers ::__construct()
	 * @dataProvider provide_testConstructor_data
	 * @param mixed $expected Expected property value
	 * @param string $property Property name
	 */
	public function testConstructor($expected, $property)
	{
		$this->assertAttributeEquals($expected, $property, self::$kernel);
	}
	
	/**
	 * @covers ::__construct()
	 * @depends testConstructor
	 */
	public function testConstructorLoadClassCache()
	{
		$this->setUp('working', 'app', 'prod', false);
		
		// Check the loadClassCache property has been initialized correctly
		$this->assertAttributeEquals(array('classes','.php'), 'loadClassCache', self::$kernel);
	}
	
	/**
	 * @covers ::getCacheDir()
	 */
	public function testGetCacheDir()
	{
		$expected = dirname(dirname(self::$kernel->getRootDir())) . '/var/cache/app/test';
		
		// Check that getCacheDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getCacheDir());
		
		// Check that getCacheDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'cacheDir', self::$kernel);
	}
	
	/**
	 * @covers ::getLogDir()
	 */
	public function testGetLogDir()
	{
		$expected = dirname(dirname(self::$kernel->getRootDir())) . '/var/logs/app';
		
		// Check that getLogDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getLogDir());
		
		// Check that getLogDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'logDir', self::$kernel);
	}
	
	/**
	 * @covers ::registerContainerConfiguration()
	 */
	public function testRegisterContainerConfiguration()
	{
		self::$kernel->boot();
		$container = self::$kernel->getContainer();
		
		// Check the container has the kernel.secret parameter
		$this->assertEquals('ThisTokenIsNotSoSecretChangeIt', $container->getParameter('kernel.secret'));
	}

	/**
	 * @covers ::registerContainerConfiguration()
	 * @expectedException Symfony\Component\Config\Exception\FileLocatorFileNotFoundException
	 * @expectedExceptionMessageRegExp |^The file "(.*)/fixtures/kernels/working/apps/app/config/config_invalid.yml" does not exist.$|
	 */
	public function testRegisterContainerConfigurationThrowsException()
	{
		$this->writeAttribute(self::$kernel, 'environment', 'invalid');
		
		self::$kernel->boot();
	}
}
