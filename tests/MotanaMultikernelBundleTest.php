<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;
use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\OverrideAssetsPathPackageServicePass;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle
 */
class MotanaMultikernelBundleTest extends ApplicationTestCase
{
	/**
	 * @covers ::build()
	 */
	public function testBuildDoesNotAddCompilerPassesOnBootKernel()
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
	 */
	public function testBuildAddsCompilerPassesOnAppKernels()
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
	 */
	public function testRegisterCommands()
	{
		// Check the "boot" kernel has commands in the multi-kernel namespace
		$app = self::$application;
		$app->find('multikernel:create-app');
	}
	
	/**
	 * @covers ::registerCommands()
	 * @depends testRegisterCommands
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage There are no commands defined in the "multikernel" namespace.
	 */
	public function testRegisterCommandsThrowsException()
	{
		// Check the "app" kernel has no commands in the multi-kernel namespace
		$app = $this->callMethod(self::$application, 'getApplication', 'app');
		$app->find('multikernel:create-app');
	}
}
