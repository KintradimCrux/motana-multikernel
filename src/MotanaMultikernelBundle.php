<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle;

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;

/**
 * Bundle.
 * 
 * @author torr
 */
class MotanaMultikernelBundle extends Bundle
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Bundle\Bundle::build()
	 */
	public function build(ContainerBuilder $container)
	{
		parent::build($container);
		
		$container->addCompilerPass(new ExcludeClassesFromCachePass(), PassConfig::TYPE_OPTIMIZE);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Bundle\Bundle::registerCommands()
	 */
	public function registerCommands(Application $application)
	{
		if ('boot' !== $application->getKernel()->getName()) {
			return;
		}
		
		parent::registerCommands($application);
	}
}
