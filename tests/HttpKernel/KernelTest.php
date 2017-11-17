<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\HttpKernel;

use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase;
use Motana\Component\HttpKernel\Kernel;

use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\Kernel
 * @testdox Motana\Bundle\MultikernelBundle\HttpKernel\Kernel
 */
class KernelTest extends KernelTestCase
{
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Get the fixture directory
		$env = getenv('__MULTIKERNEL_FIXTURE_DIR');
		
		// Check the attributes are initialized correctly
		$this->assertAttributeEquals(null, 'cacheDir', self::$kernel);
		$this->assertAttributeEquals(null, 'logDir', self::$kernel);
		$this->assertAttributeEquals([], 'bundles', self::$kernel);
		$this->assertAttributeEquals(null, 'bundleMap', self::$kernel);
		$this->assertAttributeEquals(null, 'container', self::$kernel);
		$this->assertAttributeEquals($env . '/default/apps/app', 'rootDir', self::$kernel);
		$this->assertAttributeEquals('test', 'environment', self::$kernel);
		$this->assertAttributeEquals(false, 'debug', self::$kernel);
		$this->assertAttributeEquals(false, 'booted', self::$kernel);
		$this->assertAttributeEquals('app', 'name', self::$kernel);
		$this->assertAttributeEquals(null, 'loadClassCache', self::$kernel);
	}
	
	/**
	 * @covers ::__construct()
	 * @kernelEnvironment prod
	 * @requires PHP < 7.0
	 * @testdox __construct() loads class cache for PHP version 5.x
	 */
	public function test_constructor_with_php_5()
	{
		$this->setUp('app', 'prod', false);
		
		// Check the loadClassCache property has been initialized correctly
		$this->assertAttributeEquals([ 'classes','.php' ], 'loadClassCache', self::$kernel);
	}
	
	/**
	 * @covers ::__construct()
	 * @kernelEnvironment prod
	 * @requires PHP 7.0
	 * @testdox __construct() does not load class cache for PHP version 7.x
	 */
	public function test_constructor_with_php_7()
	{
		$this->setUp('app', 'prod', false);
		
		// Check the loadClassCache property has been initialized correctly
		$this->assertAttributeEquals(null, 'loadClassCache', self::$kernel);
	}
	
	/**
	 * @covers ::getCacheDir()
	 * @testdox getCacheDir() returns correct path
	 */
	public function test_getCacheDir()
	{
		$expected = dirname(dirname(self::$kernel->getRootDir())) . '/var/cache/app/test';
		
		// Check that getCacheDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getCacheDir());
		
		// Check that getCacheDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'cacheDir', self::$kernel);
	}
	
	/**
	 * @covers ::getLogDir()
	 * @testdox getLogDir() returns correct path
	 */
	public function test_getLogDir()
	{
		$expected = dirname(dirname(self::$kernel->getRootDir())) . '/var/logs/app';
		
		// Check that getLogDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getLogDir());
		
		// Check that getLogDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'logDir', self::$kernel);
	}

	/**
	 * @covers ::registerContainerConfiguration()
	 * @testdox registerContainerConfiguration() loads configuration
	 */
	public function test_registerContainerConfiguration()
	{
		$this->callMethod(self::$kernel, 'initializeBundles');
		$container = $this->callMethod(self::$kernel, 'buildContainer');
		
		// Check the container has the kernel.secret parameter
		$this->assertRegExp('|^[0-9a-f]{40}$|', $container->getParameter('secret'));
	}
	
	/**
	 * @covers ::registerContainerConfiguration()
	 * @expectedException Symfony\Component\Config\Exception\FileLocatorFileNotFoundException
	 * @expectedExceptionMessageRegExp |^The file "(.*)/apps/app/config/config_invalid.yml" does not exist.$|
	 * @testdox registerContainerConfiguration() throws FileLocatorFileNotFoundException for invalid environment
	 */
	public function test_registerContainerConfiguration_with_invalid_environment()
	{
		$this->writeAttribute(self::$kernel, 'environment', 'invalid');
		
		// Check an exception is thrown when trying to boot the kernel with an invalid environment
		self::$kernel->boot();
	}
	
	/**
	 * @covers ::getContainerClass()
	 * @testdox getContainerClass() returns container class name built from camelized kernel name
	 */
	public function test_getContainerClass()
	{
		// Get the kernel name, environment and debug flag
		$kernelName = self::$kernel->getName();
		$kernelEnvironment = self::$kernel->getEnvironment();
		$kernelDebug = self::$kernel->isDebug();
		
		// Generate the expected container class name
		$expected = lcfirst(Container::camelize($kernelName)).ucfirst($kernelEnvironment).($kernelDebug ? 'Debug' : '').'ProjectContainer';
		
		// Check the kernel returns the correct container classname
		$this->assertEquals($expected, $this->callMethod(self::$kernel, 'getContainerClass'));
	}
}
