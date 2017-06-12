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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extension for the Motana Multi-Kernel Bundle.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MotanaMultiKernelExtension extends Extension
{
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Extension\ExtensionInterface::load()
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);
		
		$extensionName = $this->getAlias();
		foreach ($config as $parameterName => $value) {
			$container->setParameter($extensionName . '.' . $parameterName, $value);
		}
		
		$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('class_cache.xml');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Extension\Extension::getXsdValidationBasePath()
	 */
	public function getXsdValidationBasePath()
	{
		return __DIR__ . '/../Resources/config/schema';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Extension\Extension::getNamespace()
	 */
	public function getNamespace()
	{
		return 'http://symfony.com/schema/dic/motana_multi_kernel';
	}
	
	// }}}
}
