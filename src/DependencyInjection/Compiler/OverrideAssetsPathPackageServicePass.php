<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler;

use Motana\Bundle\MultikernelBundle\Asset\PathPackage;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass for overriding the class name of the 'assets.path_package' service.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class OverrideAssetsPathPackageServicePass implements CompilerPassInterface
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface::process()
	 */
	public function process(ContainerBuilder $container)
	{
		// Get the definition of the assets.path_package service
		$definition = $container->getDefinition('assets.path_package');
		
		// Override the class name of the definition
		$definition->setClass(PathPackage::class);
	}
}
