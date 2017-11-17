<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase;

use Composer\Autoload\ClassLoader;

use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;

use Symfony\Component\Finder\Finder;

/**
 * Base class for tests requiring a kernel.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class KernelTestCase extends TestCase
{
	/**
	 * Current working directory before starting the tests.
	 *
	 * @var string
	 */
	protected static $cwd;
	
	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static $fixturesDir;
	
	/**
	 * The kernel to test.
	 *
	 * @var Kernel
	 */
	protected static $kernel;
	
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
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($app = 'app', $environment = 'test', $debug = false)
	{
		// Change to the fixtures directory
		chdir(self::$fixturesDir);
		
		// Load the kernel
		self::$kernel = $this->loadFixtureKernel($app, $environment, $debug);
	}
	
	/**
	 * Remove the autoloader modifications for a test environment when finished.
	 *
	 * @afterClass
	 */
	public static function tearDownTestEnvironment()
	{
		// Fixtures dir property is non-empty
		if (null !== self::$fixturesDir)
		{
			// Get the path of the src directory
			$srcDir = self::$fixturesDir . '/src';

			// Restore the autoloader psr-4 fallback dirs
			$loader = current(current(spl_autoload_functions()));
			/** @var ClassLoader $loader */
			self::writeAttribute($loader, 'fallbackDirsPsr4', array_filter(self::readAttribute($loader, 'fallbackDirsPsr4'), function($dir) use ($srcDir) {
				return $dir !== $srcDir;
			}));
		}
	}
	
	/**
	 * Returns the fixture directory for the specified type and app.
	 *
	 * @param string $app App subdirectory name (if not loading a BootKernel)
	 * @return string
	 */
	protected static function getFixturesDir($app = null)
	{
		// Return the path to the app dir for the specified environment
		return self::$fixturesDir . '/apps' . ($app ? '/' . $app : '');
	}
	
	/**
	 * Returns the var directory for the specified type.
	 *
	 * @return string
	 */
	private static function getVarDir()
	{
		// Return the path to the var dir for the specified environment
		return self::$fixturesDir . '/var';
	}
	
	/**
	 * Returns the path of the fixture kernel class in the specified directory.
	 * Returns FALSE if no file is available.
	 *
	 * @param string $dir Directory name
	 * @return mixed
	 */
	private static function findFixtureKernel($dir)
	{
		// Find a kernel class in the specified path
		$file = current(iterator_to_array(Finder::create()
			->files()
			->name('*Kernel.php')
			->depth(0)
			->in($dir)
		));
		
		// Return the full path to the file if a file was found
		$file = false === $file ? $file : $file->getPathname();
		return $file;
	}
	
	/**
	 * Returns the path of the fixture cache class in the specified directory.
	 * Returns FALSE if no file is available.
	 *
	 * @param string $dir Directory name
	 * @return mixed
	 */
	private static function findFixtureCache($dir)
	{
		// Find a cache class in the specified path
		$file = current(iterator_to_array(Finder::create()
			->files()
			->name('*Cache.php')
			->depth(0)
			->in($dir)
		));
		
		// Return the full path to the file if a file was found
		$file = false === $file ? $file : $file->getPathname();
		return $file;
	}
	
	/**
	 * Loads the kernel and cache class from a fixture directory.
	 *
	 * @param string $app App subdirectory name
	 * @param string $environment Kernel envirionment
	 * @param string $debug Debug mode
	 * @throws \InvalidArgumentException
	 */
	private static function loadFixtureKernel($app = 'app', $environment = 'test', $debug = false)
	{
		// Get the paths for the kernel and cache classes
		$kernel = self::findFixtureKernel(static::getFixturesDir($app));
		$cache = self::findFixtureCache(static::getFixturesDir($app));
		
		// Check there is a kernel
		if (false === $kernel) {
			throw new \InvalidArgumentException(vsprintf('Cannot find fixture kernel class for app=%s', [
				var_export($app, true)
			]));
		}
		
		// Load the kernel class
		require_once($kernel);
		
		// Load the cache class if available
		if (false !== $cache) {
			require_once($cache);
		}
		
		// Get the kernel class name
		$class = basename($kernel, '.php');
		
		// Return a new instance of the kernel class
		return new $class($environment, $debug);
	}
}
