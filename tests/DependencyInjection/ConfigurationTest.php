<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\ScalarNode;

use Motana\Bundle\MultiKernelBundle\DependencyInjection\Configuration;
use Motana\Bundle\MultiKernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\DependencyInjection\Configuration
 */
class ConfigurationTest extends TestCase
{
	/**
	 * @var ConfigurationInterface
	 */
	protected $configuration;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$this->configuration = new Configuration();
	}
	
	/**
	 * @covers ::getConfigTreeBuilder()
	 */
	public function testGetConfigTreeBuilder()
	{
		$treeBuilder = $this->configuration->getConfigTreeBuilder();
		$tree = $treeBuilder->buildTree();
		/** @var ArrayNode $tree */

		$expected = array(
			'default' => null,
		);
		
		$config = array();
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
		
		// Check that the configuration tree returns the correct settings and default values
		$this->assertEquals($expected, $config);
	}
}
