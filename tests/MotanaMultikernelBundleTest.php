<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MotanaMultiKernelBundle;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;
use Motana\Bundle\MultikernelBundle\MotanaMultiKernelBundle;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\MotanaMultiKernelBundle
 */
class MotanaMultikernelBundleTest extends ApplicationTestCase
{
	/**
	 * @covers ::build()
	 */
	public function testBuild()
	{
		$container = new ContainerBuilder();
		$bundle = new MotanaMultiKernelBundle();
		
		$bundle->build($container);
		
		$passes = $container->getCompilerPassConfig()->getOptimizationPasses();
		
		// Check that the compiler pass has been added
		$this->assertInstanceOf(ExcludeClassesFromCachePass::class, end($passes));
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
		$app = $this->callMethod(self::$application, 'getApplication', 'app');
		$app->find('multikernel:create-app');
	}
}
