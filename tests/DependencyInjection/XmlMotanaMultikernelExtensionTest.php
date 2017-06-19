<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultiKernelExtension
 */
class XmlMotanaMultikernelExtensionTest extends GenericMotanaMultikernelExtensionTest
{
	/**
	 * {@inheritDoc}
	 * @see \Tests\Motana\Bundle\MultikernelBundle\GenericMotanaMultiKernelExtensionTest::loadConfiguration()
	 */
	protected function loadConfiguration(ContainerBuilder $container, $resource)
	{
		$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../fixtures/config/xml'));
		$loader->load($resource . '.xml');
	}
}
