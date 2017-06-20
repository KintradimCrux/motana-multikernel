<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * FrameworkExtraBundle configuration.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class Configuration implements ConfigurationInterface
{
	// {{{ Interface ConfigurationInterface
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Config\Definition\ConfigurationInterface::getConfigTreeBuilder()
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('motana_multikernel', 'array');

		$rootNode
			->children()
				->scalarNode('default')
					->defaultNull()
					->info('Default kernel the front controller should load when no kernel matches the URL')
					->example('"app" for the default AppKernel')
				->end()
				->arrayNode('class_cache')
					->info('Class cache configuration')
					->children()
						->arrayNode('exclude')
							->beforeNormalization()->castToArray()->end()
							->defaultValue(array())
							->info('Classes to exclude from being cached in classes.php of app kernels')
							->prototype('scalar')
							->end()
						->end()
					->end()
				->end()
				->arrayNode('commands')
					->info('Console commands configuration')
					->children()
						->arrayNode('add')
							->beforeNormalization()->castToArray()->end()
							->defaultValue(array())
							->info('Commands to add as multi-kernel command, bypassing the requirement of being available for all kernels')
							->prototype('scalar')
							->end()
						->end()
						->arrayNode('global')
							->beforeNormalization()->castToArray()->end()
							->defaultValue(array())
							->info('Commands that will always be run on the boot kernel and will be hidden in the other kernels')
							->prototype('scalar')
							->end()
						->end()
						->arrayNode('hidden')
							->beforeNormalization()->castToArray()->end()
							->defaultValue(array())
							->info('Commands that will be hidden in all kernels')
							->prototype('scalar')
							->end()
						->end()
					->end()
				->end()
			->end()
		;
		
		return $treeBuilder;
	}
	
	// }}}
}
