<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\DependencyInjection\Compiler;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass
 * @testdox Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass
 */
class ExcludeClassesFromCachePassTest extends KernelTestCase
{
	/**
	 * @var array
	 */
	protected $classes;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($app = 'app', $environment = 'test', $debug = false)
	{
		parent::setUp($app, $environment, $debug);
		
		$this->classes = $this->getExcludedClasses();
	}
	
	/**
	 * Loads the class cache configuration and returns the classes listed in it.
	 *
	 * @return array
	 */
	protected function getExcludedClasses()
	{
		$classes = [];
		$bundle = new MotanaMultikernelBundle();
		
		if (is_file($file = $bundle->getPath() . '/Resources/config/class_cache.xml')) {
			$xml = new \SimpleXMLElement($file, LIBXML_NOCDATA, true);
			foreach ($xml->{'parameters'}->{'parameter'}->{'parameter'} as $parameter) {
				$classes[] = (string) $parameter;
			}
		}
		
		return $classes;
	}
	
	/**
	 * @covers ::process()
	 * @testdox process() adds classes to class cache exclude list
	 */
	public function test_process()
	{
		$this->callMethod(self::$kernel, 'initializeBundles');
		
		$container = $this->callMethod(self::$kernel, 'buildContainer');
		/** @var ContainerBuilder $container */
		
		$passes = array_map(function($e) {
			return get_class($e);
		}, $container->getCompilerPassConfig()->getOptimizationPasses());
		
			// Check the ExcludeClassesFromCachePass has been added
		$this->assertTrue(in_array(ExcludeClassesFromCachePass::class, $passes));
		
		// Compile the container
		$container->compile();
		
		// Check the ExcludeClassesFromCachePass has done its work
		$this->assertTrue($container->hasDefinition('kernel.class_cache.cache_warmer'));
		$definition = $container->getDefinition('kernel.class_cache.cache_warmer');
		
		// Get the first argument (an array of class names to add to cache)
		$classes = $definition->getArgument(0);
		
		// Check the array contains the class names the compiler pass should add
		foreach ($this->classes as $class) {
			$this->assertContains($class, $classes);
		}
	}
}
