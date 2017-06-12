<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;
use Motana\Bundle\MultiKernelBundle\MotanaMultiKernelBundle;
use Motana\Bundle\MultiKernelBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass
 */
class ExcludeClassesFromCachePassTest extends KernelTestCase
{
	/**
	 * @var array
	 */
	protected $classes;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = 'app', $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
		
		$this->classes = $this->getExcludedClasses();
	}
	
	/**
	 * Loads the class cache configuration and returns the classes listed in it.
	 *
	 * @return array
	 */
	protected function getExcludedClasses()
	{
		$classes = array();
		$bundle = new MotanaMultiKernelBundle();
		
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
	 */
	public function testProcess()
	{
		$this->callMethod(self::$kernel, 'initializeBundles');
		
		$container = $this->callMethod(self::$kernel, 'buildContainer');
		/** @var ContainerBuilder $container */
		
		$passes = $container->getCompilerPassConfig()->getOptimizationPasses();
		
		// Check the AddKernelsToCachePass has been added
		$this->assertInstanceOf(ExcludeClassesFromCachePass::class, end($passes));
		
		$container = $this->callMethod(self::$kernel, 'buildContainer');
		$container->compile();
		
		// Check the AddKernelsToCachePass has done its work
		$this->assertTrue($container->hasDefinition('kernel.class_cache.cache_warmer'));
		$definition = $container->getDefinition('kernel.class_cache.cache_warmer');
		
		$classes = $definition->getArgument(0);
		
		foreach ($this->classes as $class) {
			$this->assertContains($class, $classes);
		}
	}
}
