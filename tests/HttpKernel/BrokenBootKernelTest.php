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

use Symfony\Component\Debug\DebugClassLoader;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
 * @kernelDir broken
 * @kernelDebug false
 * @kernelEnvironment broken
 * @testdox Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel (env: broken)
 */
 class BrokenBootKernelTest extends KernelTestCase
{
	/**
	 * Set up the test environment before the tests run.
	 *
	 * @beforeClass
	 */
	public static function setUpTestEnvironment()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set the fixtures directory for the tests
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/broken';
		
		// Adjust the autoloader psr-4 fallback dirs
		$loader = current(current(spl_autoload_functions()));
		if ($loader instanceof DebugClassLoader) {
			$loader = current($loader->getClassLoader());
		}
		/** @var ClassLoader $loader */
		self::writeAttribute($loader, 'fallbackDirsPsr4', array_merge(self::readAttribute($loader, 'fallbackDirsPsr4'), [
			self::$fixturesDir . '/src',
		]));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'broken', $debug = false)
	{
		parent::setUp($app, $environment, $debug);
	}
	
	/**
	 * @covers ::loadKernel()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Kernel class "BrokenKernelKernel" does not exist. Did you name your kernel class correctly?
	 * @testdox loadKernel() checks the AppKernel class exists
	 */
	public function test_loadKernel()
	{
		self::callMethod(self::$kernel, 'loadKernel', 'broken_kernel');
	}
	
	/**
	 * @covers ::loadKernel()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Cache class "BrokenCacheCache" does not exist. Did you name your cache class correctly?
	 * @testdox loadKernel() checks the AppCache class exists
	 */
	public function test_loadKernel_with_cache()
	{
		self::$kernel->useAppCache(true);
		self::callMethod(self::$kernel, 'loadKernel', 'broken_cache');
	}
}
