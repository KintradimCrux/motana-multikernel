<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\DependencyInjection\Compiler;

use Motana\Bundle\MultikernelBundle\Asset\PathPackage;
use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\OverrideAssetsPathPackageServicePass;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\OverrideAssetsPathPackageServicePass
 * @testdox Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\OverrideAssetsPathPackageServicePass
 */
class OverrideAssetsPathPackageServicePassTest extends KernelTestCase
{
	/**
	 * @covers ::process()
	 * @testdox process() overrides 'assets.path_package' service class name
	 */
	public function test_process()
	{
		$this->callMethod(self::$kernel, 'initializeBundles');
		
		$container = $this->callMethod(self::$kernel, 'buildContainer');
		/** @var ContainerBuilder $container */
		
		$passes = array_map(function($e) {
			return get_class($e);
		}, $container->getCompilerPassConfig()->getBeforeOptimizationPasses());
		
		// Check the OverrideAssetsPathPackageServicePasshas been added
		$this->assertTrue(in_array(OverrideAssetsPathPackageServicePass::class, $passes));
		
		$container->compile();
		
		// Check the OverrideAssetsPathPackageServicePass has replaced the 'assets.path_package' service class name
		$definition = $container->getDefinition('assets.packages')->getArgument(0);
		$this->assertEquals(PathPackage::class, $definition->getClass());
	}
}
