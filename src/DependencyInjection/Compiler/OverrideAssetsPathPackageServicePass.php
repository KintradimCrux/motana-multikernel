<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultikernelBundle\Asset\PathPackage;

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
		$definition = $container->getDefinition('assets.path_package');
		$definition->setClass(PathPackage::class);
	}
}
