<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler;

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
		// Get the definition of the cache warmer service
		$definition = $container->getDefinition('kernel.class_cache.cache_warmer');
		
		// Merge its first parameter with the class cache excludes
		$classes = array_merge($definition->getArgument(0), $container->getParameter('motana.multikernel.class_cache.exclude'));
		
		// Sort the class names
		sort($classes);
		
		// Replace the first service argument by the merged class list
		$definition->replaceArgument(0, $classes);
	}
}
