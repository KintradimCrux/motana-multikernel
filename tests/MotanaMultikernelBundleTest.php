<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests;

use Motana\Bundle\MultikernelBundle\Command\MultikernelConvertCommand;
use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;
use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\OverrideAssetsPathPackageServicePass;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle
 * @testdox Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle
 */
class MotanaMultikernelBundleTest extends ApplicationTestCase
{
	/**
	 * @covers ::build()
	 * @testdox build() does not add compiler passes on BootKernel
	 */
	public function test_build_with_BootKernel()
	{
		$container = new ContainerBuilder();
		$container->setParameter('kernel.name', 'boot');
		
		$bundle = new MotanaMultikernelBundle();
		$bundle->build($container);
		
		// Check that the ExcludeClassesFromCachePass has been added
		$passes = array_map(function($e) {
			return get_class($e);
		}, $container->getCompilerPassConfig()->getOptimizationPasses());
		$this->assertFalse(in_array(ExcludeClassesFromCachePass::class, $passes));
		
		// Check that the OverrideAssetsPathPackageServicePasshas been added
		$passes = array_map(function($e) {
			return get_class($e);
		}, $container->getCompilerPassConfig()->getBeforeOptimizationPasses());
		$this->assertFalse(in_array(OverrideAssetsPathPackageServicePass::class, $passes));
	}

	/**
	 * @covers ::build()
	 * @testdox build() adds compiler passes on AppKernel
	 */
	public function test_build_with_AppKernel()
	{
		$container = new ContainerBuilder();
		$container->setParameter('kernel.name', 'app');
		
		$bundle = new MotanaMultikernelBundle();
		$bundle->build($container);
		
		// Check that the ExcludeClassesFromCachePass has been added
		$passes = array_map(function($e) {
			return get_class($e);
		}, $container->getCompilerPassConfig()->getOptimizationPasses());
		$this->assertTrue(in_array(ExcludeClassesFromCachePass::class, $passes));
			
		// Check that the OverrideAssetsPathPackageServicePasshas been added
		$passes = array_map(function($e) {
			return get_class($e);
		}, $container->getCompilerPassConfig()->getBeforeOptimizationPasses());
		$this->assertTrue(in_array(OverrideAssetsPathPackageServicePass::class, $passes));
	}
	
	/**
	 * @covers ::registerCommands()
	 * @testdox registerCommands() registers multikernel commands for BootKernel
	 */
	public function test_registerCommands_with_BootKernel()
	{
		// Check the "boot" kernel has commands in the multi-kernel namespace
		$app = self::$application;
		$command = $app->find('multikernel:convert');
		
		// Check the returned command is an instance of the correct class
		$this->assertInstanceOf(MultikernelConvertCommand::class, $command);
	}
	
	/**
	 * @covers ::registerCommands()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "generate:app" does not exist.
	 * @testdox registerCommands() does not register multikernel commands for AppKernel
	 */
	public function test_registerCommands_with_AppKernel()
	{
		// Check the "app" kernel has no commands in the multi-kernel namespace
		$app = $this->callMethod(self::$application, 'getApplication', 'app');
		$app->find('generate:app');
	}
}
