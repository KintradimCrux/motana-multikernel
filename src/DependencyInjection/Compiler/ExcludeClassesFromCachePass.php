<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Exclude additional classes from being cached in classes.php.
 * This is required for Multi-Kernel applications.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ExcludeClassesFromCachePass implements CompilerPassInterface
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface::process()
	 */
	public function process(ContainerBuilder $container)
	{
		if ($container->hasDefinition('kernel.class_cache.cache_warmer')){
			$definition = $container->getDefinition('kernel.class_cache.cache_warmer');
			if ($container->hasParameter('motana_multi_kernel.class_cache.exclude')) {
				$classes = array_merge($definition->getArgument(0), $container->getParameter('motana_multi_kernel.class_cache.exclude'));
				
				sort($classes);
				
				$definition->replaceArgument(0, $classes);
			}
		}
	}
}
