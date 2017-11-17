<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\DependencyInjection;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Configuration;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\ScalarNode;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\Configuration
 * @testdox Motana\Bundle\MultikernelBundle\DependencyInjection\Configuration
 */
class ConfigurationTest extends TestCase
{
	/**
	 * @var ConfigurationInterface
	 */
	protected $configuration;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$this->configuration = new Configuration();
	}
	
	/**
	 * @covers ::getConfigTreeBuilder()
	 * @testdox getConfigTreeBuilder() returns correct configuration sections
	 */
	public function test_getConfigTreeBuilder()
	{
		$treeBuilder = $this->configuration->getConfigTreeBuilder();
		$tree = $treeBuilder->buildTree();
		/** @var ArrayNode $tree */

		$expected = [
			'default' => null,
			'class_cache.exclude' => [],
			'commands.add' => [],
			'commands.global' => [],
			'commands.hidden' => [],
			'commands.ignore' => [],
		];
		
		$config = [];
		foreach ($tree->getChildren() as $groupName => $group) {
			if ($group instanceof ScalarNode) {
				$config[$groupName] = $group->getDefaultValue();
			} else {
				foreach ($group->getChildren() as $nodeName => $node) {
					/** @var ScalarNode $node */
					$config[$groupName.'.'.$nodeName] = $node->getDefaultValue();
				}
			}
		}
		
		// Check the returned configuration tree contains the correct settings and default values
		$this->assertEquals($expected, $config);
	}
}
