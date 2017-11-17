<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension
 * @testdox Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension with Yaml configuration
 */
class YamlMotanaMultikernelExtensionTest extends GenericMotanaMultikernelExtensionTest
{
	/**
	 * @beforeClass
	 */
	public static function setUpFormat()
	{
		self::$format = 'yml';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\GenericMotanaMultikernelExtensionTest::loadConfiguration()
	 */
	protected function loadConfiguration(ContainerBuilder $container, $resource)
	{
		// Load the requested resource
		$loader = new YamlFileLoader($container, new FileLocator(self::$fixturesDir));
		$loader->load($resource . '.yml');
	}
}
