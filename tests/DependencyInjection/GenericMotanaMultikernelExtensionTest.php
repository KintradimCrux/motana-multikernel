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

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Test\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;

/**
 * Abstract base class for extension tests.
 *
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension
 */
abstract class GenericMotanaMultikernelExtensionTest extends TestCase
{
	/**
	 * @var MotanaMultikernelExtension
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
		$bundles = array(
			'FrameworkBundle' => FrameworkBundle::class,
			'MotanaMultikernelBundle' => MotanaMultikernelBundle::class,
		);
		
		self::$extension = new MotanaMultikernelExtension();
		
		self::$container = new ContainerBuilder();
		self::$container->setParameter('kernel.bundles', $bundles);
		self::$container->setParameter('kernel.cache_dir', __DIR__ . '/../../fixtures/kernels/working/var/cache');
		self::$container->setParameter('kernel.charset', 'UTF-8');
		self::$container->setParameter('kernel.debug', false);
		self::$container->setParameter('kernel.root_dir', __DIR__ . '/../../fixtures/kernels/working/apps');
		self::$container->setParameter('kernel.secret', 'ThisTokenIsNotSoSecretChangeIt');
		
		self::$container->registerExtension(new FrameworkExtension());
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
		$bundle = new MotanaMultikernelBundle();
		
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
		$bundle = new MotanaMultikernelBundle();
		
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
	 * @covers ::prepend()
	 */
	public function testPrependAssetsGlobal()
	{
		self::$extension->prepend(self::$container);
		
		// Check that prepend() added the assets.base_path setting
		$this->assertEquals(array(
			array(
				'assets' => array(
					'base_path' => '..',
				),
			)
		), self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::prepend()
	 * @depends testPrependAssetsGlobal
	 */
	public function testPrependAssetsGlobalWithBaseUrl()
	{
		self::$container->prependExtensionConfig('framework', array(
			'assets' => array(
				'base_url' => '//localhost/web'
			)
		));
		
		self::$extension->prepend(self::$container);
		
		$this->assertEquals(array(array(
			'assets' => array(
				'base_url' => '//localhost/web'
			)
		)), self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::prepend()
	 * @depends testPrependAssetsGlobalWithBaseUrl
	 */
	public function testPrependAssetsPackages()
	{
		self::$container->prependExtensionConfig('framework', array(
			'assets' => array(
				'packages' => array(
					'default' => array(
						
					)
				),
			)
		));
		
		self::$extension->prepend(self::$container);
		
		$this->assertEquals(array(
			array(
				'assets' => array(
					'packages' => array(
						'default' => array(
							'base_path' => '..',
						),
					),
				),
			),
			array(
				'assets' => array(
					'base_path' => '..',
				),
			),
			array(
				'assets' => array(
					'packages' => array(
						'default' => array(
						),
					),
				),
			)
		), self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::prepend()
	 * @depends testPrependAssetsPackages
	 */
	public function testPrependAssetsPackagesWithBaseUrl()
	{
		self::$container->prependExtensionConfig('framework', array(
			'assets' => array(
				'packages' => array(
					'default' => array(
						'base_url' => '//localhost/web'
					)
				),
			)
		));
		
		self::$extension->prepend(self::$container);

		$this->assertEquals(array(
			array(
				'assets' => array(
					'base_path' => '..',
				),
			),
			array(
				'assets' => array(
					'packages' => array(
						'default' => array(
							'base_url' => '//localhost/web'
						)
					),
				)
			)
		), self::$container->getExtensionConfig('framework'));
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
