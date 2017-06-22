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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extension for the Motana Multi-Kernel Bundle.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MotanaMultikernelExtension extends Extension implements PrependExtensionInterface
{
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface::prepend()
	 */
	public function prepend(ContainerBuilder $container)
	{
		$bundles = $container->getParameter('kernel.bundles');
		
		// Set the base_path of assets to '..' on all packages
		if (isset($bundles['FrameworkBundle'])) {
			$config = $container->getExtensionConfig('framework');
			if ( ! empty($config)) {
				$config = call_user_func_array('array_merge', $container->getExtensionConfig('framework'));
			}

			if ( ! isset($config['assets']['base_url'])) {
				$container->prependExtensionConfig('framework', array(
					'assets' => array(
						'base_path' => '..',
					)
				));
			}
			
			if (isset($config['assets']['packages'])) {
				foreach ($config['assets']['packages'] as $packageName => $package) {
					if ( ! isset($package['base_url'])) {
						$container->prependExtensionConfig('framework', array(
							'assets' => array(
								'packages' => array(
									$packageName => array(
										'base_path' => '..',
									)
								)
							)
						));
					}
				}
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Extension\ExtensionInterface::load()
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);
		
		$container->setParameter('motana.multikernel.default', $config['default']);
		
		$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		
		$this->registerClassCacheConfiguration($config, $container, $loader);
		$this->registerCommandsConfiguration($config, $container, $loader);
	}
	
	/**
	 * Loads and merges the class cache configuration.
	 *
	 * @param array $config Configuration options
	 * @param ContainerBuilder $container A ContainerBuilder instance
	 * @param XmlFileLoader $loader A XmlFileLoader instance
	 */
	private function registerClassCacheConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
	{
		$loader->load('class_cache.xml');
		
		if (isset($config['class_cache']['exclude'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.class_cache.exclude', array_filter((array) $config['class_cache']['exclude']));
		}
	}
	
	/**
	 * Loads and merges the console commands configuration.
	 *
	 * @param array $config Configuration options
	 * @param ContainerBuilder $container A ContainerBuilder instance
	 * @param XmlFileLoader $loader A XmlFileLoader instance
	 */
	private function registerCommandsConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
	{
		$loader->load('commands.xml');
		
		if (isset($config['commands']['add'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.add', array_filter((array) $config['commands']['add']));
		}
		
		if (isset($config['commands']['global'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.global', array_filter((array) $config['commands']['global']));
		}
		
		if (isset($config['commands']['hidden'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.hidden', array_filter((array) $config['commands']['hidden']));
		}
	}
	
	/**
	 * Merge the content of a container parameter containing an array with additional values.
	 *
	 * @param ContainerBuilder $container A ContainerBuilder instance
	 * @param string $parameterName Container parameter name
	 * @param array $values Values to add
	 */
	private function mergeContainerParameter(ContainerBuilder $container, $parameterName, array $values)
	{
		$parameter = $container->getParameter($parameterName);
		
		$container->setParameter($parameterName, array_merge($parameter, $values));
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
		return 'http://symfony.com/schema/dic/motana-multikernel';
	}
	
	// }}}
}
