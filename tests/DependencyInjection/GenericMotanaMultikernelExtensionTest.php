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

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultiKernelExtension;
use Motana\Bundle\MultikernelBundle\MotanaMultiKernelBundle;
use Motana\Bundle\MultikernelBundle\Test\TestCase;

/**
 * Abstract base class for extension tests.
 * 
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultiKernelExtension
 */
abstract class GenericMotanaMultikernelExtensionTest extends TestCase
{
	/**
	 * @var MotanaMultiKernelExtension
	 */
	protected static $extension;
	
	/**
	 * @var ContainerBuilder
	 */
	protected static $container;
	
	/**
	 * @var array
	 */
	protected static $classes = array();
	
	/**
	 * @var array
	 */
	protected static $commands = array();
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$extension = new MotanaMultiKernelExtension();
		
		self::$container = new ContainerBuilder();
		self::$container->registerExtension(self::$extension);
		
		self::$classes = $this->getDefaultClassCacheSettings();
		self::$commands = $this->getDefaultCommands();
	}
	
	/**
	 * Loads the class cache configuration and returns the classes listed in it.
	 *
	 * @return array
	 */
	protected function getDefaultClassCacheSettings()
	{
		$classes = array();
		$bundle = new MotanaMultiKernelBundle();
		
		if (is_file($file = $bundle->getPath() . '/Resources/config/class_cache.xml')) {
			$xml = new \SimpleXMLElement($file, LIBXML_NOCDATA, true);
			foreach ($xml->{'parameters'}->{'parameter'} as $parameter) {
				$key = str_replace('motana.multikernel.class_cache.', '', $parameter['key']);
				foreach ($parameter->{'parameter'} as $className) {
					$classes[$key][] = (string) $className;
				}
			}
		}
		
		return $classes;
	}
	
	/**
	 * Loads the modules configuration and returns the commands listed in it.
	 *
	 * @return array
	 */
	protected function getDefaultCommands()
	{
		$commands= array();
		$bundle = new MotanaMultiKernelBundle();
		
		if (is_file($file = $bundle->getPath() . '/Resources/config/commands.xml')) {
			$xml = new \SimpleXMLElement($file, LIBXML_NOCDATA, true);
			
			foreach ($xml->{'parameters'}->{'parameter'} as $parameter) {
				$key = str_replace('motana.multikernel.commands.', '', $parameter['key']);
				$commands[$key] = array();
				foreach ($parameter->{'parameter'} as $commandName) {
					$commands[$key][] = (string)$commandName;
				}
			}
		}
		
		return $commands;
	}
	
	/**
	 * Loads the extension configuration.
	 * 
	 * @param ContainerBuilder $container
	 * @param string $resource
	 */
	abstract protected function loadConfiguration(ContainerBuilder $container, $resource);
	
	/**
	 * @covers ::mergeContainerParameter()
	 */
	public function testMergeContainerParameter()
	{
		// Create a container with a parameter
		$container = new ContainerBuilder();
		$container->setParameter('test_parameter', array('one', 'two', 'three'));
		
		// Merge the parameter with additional values
		$this->callMethod(self::$extension, 'mergeContainerParameter', $container, 'test_parameter', array('four', 'five', 'six'));
		
		// Check the parameter contains the merged array
		$this->assertEquals(array('one', 'two', 'three', 'four', 'five', 'six'), $container->getParameter('test_parameter'));
	}
	
	/**
	 * @covers ::load()
	 * @covers ::registerClassCacheConfiguration()
	 * @covers ::registerCommandsConfiguration()
	 */
	public function testLoadWithoutConfiguration()
	{
		self::$container->loadFromExtension(self::$extension->getAlias());
		self::$container->compile();
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.default'));
		$this->assertNull(self::$container->getParameter('motana.multikernel.default'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.class_cache.exclude'));
		$this->assertEquals(self::$classes['exclude'], self::$container->getParameter('motana.multikernel.class_cache.exclude'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.add'));
		$this->assertEquals(self::$commands['add'], self::$container->getParameter('motana.multikernel.commands.add'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.global'));
		$this->assertEquals(self::$commands['global'], self::$container->getParameter('motana.multikernel.commands.global'));
	
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.hidden'));
		$this->assertEquals(self::$commands['hidden'], self::$container->getParameter('motana.multikernel.commands.hidden'));
	}

	/**
	 * @covers ::load()
	 * @covers ::registerClassCacheConfiguration()
	 * @covers ::registerCommandsConfiguration()
	 */
	public function testLoadDisabledConfiguration()
	{
		$this->loadConfiguration(self::$container, 'disabled');
		self::$container->compile();
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.default'));
		$this->assertNull(self::$container->getParameter('motana.multikernel.default'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.class_cache.exclude'));
		$this->assertEquals(self::$classes['exclude'], self::$container->getParameter('motana.multikernel.class_cache.exclude'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.add'));
		$this->assertEquals(self::$commands['add'], self::$container->getParameter('motana.multikernel.commands.add'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.global'));
		$this->assertEquals(self::$commands['global'], self::$container->getParameter('motana.multikernel.commands.global'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.hidden'));
		$this->assertEquals(self::$commands['hidden'], self::$container->getParameter('motana.multikernel.commands.hidden'));
	}
	
	/**
	 * @covers ::load()
	 * @covers ::registerClassCacheConfiguration()
	 * @covers ::registerCommandsConfiguration()
	 */
	public function testLoadEnabledConfiguration()
	{
		$this->loadConfiguration(self::$container, 'enabled');
		self::$container->compile();
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.default'));
		$this->assertEquals('app', self::$container->getParameter('motana.multikernel.default'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.class_cache.exclude'));
		$this->assertEquals(self::$classes['exclude'], self::$container->getParameter('motana.multikernel.class_cache.exclude'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.add'));
		$this->assertEquals(self::$commands['add'], self::$container->getParameter('motana.multikernel.commands.add'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.global'));
		$this->assertEquals(self::$commands['global'], self::$container->getParameter('motana.multikernel.commands.global'));
		
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.hidden'));
		$this->assertEquals(self::$commands['hidden'], self::$container->getParameter('motana.multikernel.commands.hidden'));
	}
	
	/**
	 * @covers ::getXsdValidationBasePath()
	 */
	public function testGetXsdValidationBasePath()
	{
		$class = new \ReflectionClass(self::$extension);
		$expected = dirname($class->getFileName()) . '/../Resources/config/schema';
		
		$this->assertEquals($expected, self::$extension->getXsdValidationBasePath());
	}
	
	/**
	 * @covers ::getNamespace()
	 */
	public function testGetNamespace()
	{
		$this->assertEquals('http://symfony.com/schema/dic/motana-multikernel', self::$extension->getNamespace());
	}
}
