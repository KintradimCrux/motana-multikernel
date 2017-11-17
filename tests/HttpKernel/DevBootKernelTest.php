<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\HttpKernel;

use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebServerBundle\WebServerBundle;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
 * @kernelEnvironment dev
 * @testdox Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel (env: dev)
 */
class DevBootKernelTest extends KernelTestCase
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/default';
		
		// Adjust the autoloader psr-4 fallback dirs
		$loader = current(current(spl_autoload_functions()));
		/** @var ClassLoader $loader */
		self::writeAttribute($loader, 'fallbackDirsPsr4', array_merge(self::readAttribute($loader, 'fallbackDirsPsr4'), [
			self::$fixturesDir . '/src',
		]));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'dev', $debug = false)
	{
		parent::setUp($app, $environment, $debug);
	}
	
	/**
	 * @covers ::registerBundles()
	 * @testdox registerBundles() returns the correct bundles
	 */
	public function test_registerBundles()
	{
		// Set up a development environment
		$this->setUp(null, 'dev', false);
		
		// Get registered bundles
		$bundles = self::$kernel->registerBundles();
		
		// Check that registerBundles() returned the correct number of bundles
		$this->assertEquals(4, count($bundles));
		
		// Check the returned bundles are instances of the correct classes
		$this->assertEquals(FrameworkBundle::class, get_class($bundles[0]));
		$this->assertEquals(MotanaMultikernelBundle::class, get_class($bundles[1]));
		$this->assertEquals(WebServerBundle::class, get_class($bundles[2]));
		$this->assertEquals(TwigBundle::class, get_class($bundles[3]));
	}
}
