<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\ExcludeClassesFromCachePass;
use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\OverrideAssetsPathPackageServicePass;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * MotanaMultikernelBundle.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MotanaMultikernelBundle extends Bundle
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Bundle\Bundle::build()
	 */
	public function build(ContainerBuilder $container)
	{
		// Call parent method
		parent::build($container);
		
		// Register compiler passes if not running on the boot kernel
		if ('boot' !== $container->getParameter('kernel.name')) {
			$container->addCompilerPass(new ExcludeClassesFromCachePass(), PassConfig::TYPE_OPTIMIZE);
			$container->addCompilerPass(new OverrideAssetsPathPackageServicePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
		}
	}
}
