<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('motana_multi_kernel', 'array');

        $rootNode
            ->children()
            	->scalarNode('default')
            		->defaultNull()
            	->end()
            	#->arrayNode('regular_commands')
            	#	->prototype('array')
            	#	->children()
            	#		->scalarNode('')
            	#	->end()
            	#->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
    
    // }}}
}
