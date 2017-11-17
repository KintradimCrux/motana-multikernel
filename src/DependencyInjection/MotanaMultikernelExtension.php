<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\DependencyInjection;

use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
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
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface::prepend()
	 */
	public function prepend(ContainerBuilder $container)
	{
		// Get available bundles
		$bundles = $container->getParameter('kernel.bundles');
		
		// Set the base_path of assets to '..' on all packages
		if (isset($bundles['FrameworkBundle']))
		{
			// Get existing extension configuration
			$config = $container->getExtensionConfig('framework');
			if ( ! empty($config)) {
				$config = call_user_func_array('array_merge', $container->getExtensionConfig('framework'));
			}
			
			// Set the secret parameter on the boot kernel
			$class = Container::camelize($container->getParameter('kernel.name')) . 'Kernel';
			if (class_exists($class) && is_subclass_of($class, BootKernel::class)) {
				$container->prependExtensionConfig('framework', [
					'secret' => '$ecret',
				]);
			}
			
			// Set the assets.base_path option if assets.base_url is not set
			if ( ! isset($config['assets']['base_url'])) {
				$container->prependExtensionConfig('framework', [
					'assets' => [
						'base_path' => '..',
					]
				]);
			}
			
			// Set the assets.packages.*.base_path option if assets.packages.*.base_url is not set
			if (isset($config['assets']['packages'])) {
				foreach ($config['assets']['packages'] as $packageName => $package) {
					if ( ! isset($package['base_url'])) {
						$container->prependExtensionConfig('framework', [
							'assets' => [
								'packages' => [
									$packageName => [
										'base_path' => '..',
									]
								]
							]
						]);
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
		// Process configuration
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);
		
		// Set the default kernel parameter
		$container->setParameter('motana.multikernel.default', $config['default']);
		
		// Create a loader for configuration files
		$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		
		// Load class cache configuration
		$this->registerClassCacheConfiguration($config, $container, $loader);
		
		// Load commands configuration
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
		// Load the class cache configuration
		$loader->load('class_cache.xml');
		
		// Merge the class_cache.exclude parameter
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
		// Load the commands configuration
		$loader->load('commands.xml');
		
		// Merge the commands.add parameter
		if (isset($config['commands']['add'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.add', array_filter((array) $config['commands']['add']));
		}
		
		// Merge the commands.global parameter
		if (isset($config['commands']['global'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.global', array_filter((array) $config['commands']['global']));
		}
		
		// Merge the commands.hidden parameter
		if (isset($config['commands']['hidden'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.hidden', array_filter((array) $config['commands']['hidden']));
		}
		
		// Merge the commands.ignore parameter
		if (isset($config['commands']['ignore'])) {
			$this->mergeContainerParameter($container, 'motana.multikernel.commands.ignore', array_filter((array) $config['commands']['ignore']));
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
		// Get the current parameter value
		$parameter = $container->getParameter($parameterName);
		
		// Merge current value with specified value and set the parameter
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
}
