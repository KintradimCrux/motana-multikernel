<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Test;

use Symfony\Component\Finder\Finder;

use Motana\Bundle\MultiKernelBundle\HttpKernel\Kernel;
use Motana\Bundle\MultiKernelBundle\HttpKernel\BootKernel;

/**
 * Base class for tests requiring a kernel.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class KernelTestCase extends TestCase
{
	/**
	 * @var string
	 */
	protected static $fixturesDir = __DIR__ . '/../../fixtures';
	
	/**
	 * @var string
	 */
	protected static $varDir;
	
	/**
	 * @var Kernel
	 */
	protected static $kernel;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = 'app', $environment = 'test', $debug = false)
	{
		self::$kernel = $this->loadFixtureKernel($type, $app, $environment, $debug);
		self::$varDir = self::getVarDir($type);
	}
	
	/**
	 * Remove cache files generated during the tests.
	 * 
	 * @afterClass
	 */
	public static function tearDownVarDir()
	{
		if (is_dir(self::$varDir)) {
			self::getFs()->remove(self::$varDir);
		}
	}
	
	/**
	 * Returns the fixture directory for the specified type and app.
	 * 
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name (if not loading a BootKernel)
	 * @return string
	 */
	private static function getFixturesDir($type, $app = null)
	{
		return self::$fixturesDir . '/kernels/' . $type . '/apps' .($app ? '/' . $app : '');
	}
	
	/**
	 * Returns the var directory for the specified type.
	 * 
	 * @param string $type Type (broken | working)
	 * @return string
	 */
	private static function getVarDir($type)
	{
		return self::$fixturesDir . '/kernels/' . $type . '/var';
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
		$file = current(iterator_to_array(Finder::create()
			->files()
			->name('*Kernel.php')
			->depth(0)
			->in($dir)
		));
		
		return false == $file ? $file : $file->getPathname();
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
		$file = current(iterator_to_array(Finder::create()
			->files()
			->name('*Cache.php')
			->depth(0)
			->in($dir)
		));
		
		return false == $file ? $file : $file->getPathname();
	}
	
	/**
	 * Loads the kernel and cache class from a fixture directory.
	 * 
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $environment Kernel envirionment
	 * @param string $debug Debug mode
	 * @throws \InvalidArgumentException
	 */
	private static function loadFixtureKernel($type = 'working', $app = 'app', $environment = 'test', $debug = false)
	{
		$kernel = self::findFixtureKernel(self::getFixturesDir($type, $app));
		$cache = self::findFixtureCache(self::getFixturesDir($type, $app));
		
		if (false === $kernel) {
			throw new \InvalidArgumentException(vsprintf('Cannot find fixture kernel class for type=%s, app=%s', array(
				var_export($type, true),
				var_export($app, true)
			)));
		}
		
		require_once($kernel);
		if (false !== $cache) {
			require_once($cache);
		}
		
		$class = basename($kernel, '.php');
		
		return new $class($environment, $debug);
	}
}
