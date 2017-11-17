<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\DependencyInjection;

use Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension;
use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Abstract base class for extension tests.
 *
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension
 * @testdox Motana\Bundle\MultikernelBundle\DependencyInjection\MotanaMultikernelExtension
 */
abstract class GenericMotanaMultikernelExtensionTest extends TestCase
{
	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static $fixturesDir;
	
	/**
	 * Output for messages from the generstors.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * File format used for the test.
	 *
	 * @var string
	 */
	protected static $format;
	
	/**
	 * The extension to test.
	 *
	 * @var MotanaMultikernelExtension
	 */
	protected static $extension;
	
	/**
	 * The container builder used for the test.
	 *
	 * @var ContainerBuilder
	 */
	protected static $container;
	
	/**
	 * Classes configuration of the MotanaMultikernelBundle.
	 *
	 * @var array
	 */
	protected static $classes = [];
	
	/**
	 * Commands configuration of the MotanaMultikernelBundle.
	 *
	 * @var array
	 */
	protected static $commands = [];
	
	/**
	 * Set up the format property for the tests.
	 *
	 * @return void
	 */
	public static function setUpFormat()
	{
		// Override this in concrete extension tests
	}
	
	/**
	 * Set up the test environment before the tests run.
	 *
	 * @beforeClass
	 */
	public static function setUpTestEnvironment()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set up the format property
		static::setUpFormat();
		
		// Generate a temporary directory name for the test
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/extension/' . self::$format;
		
		self::$output = new BufferedOutput();
		self::writeAttribute(Generator::class, 'output', self::$output);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$bundles = [
			'FrameworkBundle' => FrameworkBundle::class,
			'MotanaMultikernelBundle' => MotanaMultikernelBundle::class,
		];
		
		self::$extension = new MotanaMultikernelExtension();
		
		// Insert required parameters into the container
		self::$container = new ContainerBuilder();
		self::$container->setParameter('kernel.bundles', $bundles);
		self::$container->setParameter('kernel.cache_dir', self::$fixturesDir . '/var/cache/boot');
		self::$container->setParameter('kernel.charset', 'UTF-8');
		self::$container->setParameter('kernel.debug', false);
		self::$container->setParameter('kernel.name', 'boot');
		self::$container->setParameter('kernel.root_dir', self::$fixturesDir . '/apps');
		self::$container->setParameter('kernel.secret', 'ThisTokenIsNotSoSecretChangeIt');
		
		// Register required extensions
		self::$container->registerExtension(new FrameworkExtension());
		self::$container->registerExtension(self::$extension);
		
		// Get MotanaMultikernelBundle configuration
		self::$classes = $this->getDefaultClassCacheSettings();
		self::$commands = $this->getDefaultCommands();
	}
	
	/**
	 * Tear down test environment after the tests have run.
	 */
	public static function tearDownTestEnvironment()
	{
		// Remove the output from the Generator classes
		self::writeAttribute(Generator::class, 'output', null);
		self::writeAttribute(FilesystemManipulator::class, 'output', null);
	}
	
	/**
	 * Loads the class cache configuration and returns the classes listed in it.
	 *
	 * @return array
	 */
	protected function getDefaultClassCacheSettings()
	{
		$classes = [];
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
		$commands= [];
		$bundle = new MotanaMultikernelBundle();
		
		if (is_file($file = $bundle->getPath() . '/Resources/config/commands.xml')) {
			$xml = new \SimpleXMLElement($file, LIBXML_NOCDATA, true);
			
			foreach ($xml->{'parameters'}->{'parameter'} as $parameter) {
				$key = str_replace('motana.multikernel.commands.', '', $parameter['key']);
				$commands[$key] = [];
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
	 * @testdox mergeContainerParameter() adds values to parameters
	 */
	public function test_mergeContainerParameter()
	{
		// Create a container with a parameter
		$container = new ContainerBuilder();
		$container->setParameter('test_parameter', [ 'one', 'two', 'three' ]);
		
		// Merge the parameter with additional values
		$this->callMethod(self::$extension, 'mergeContainerParameter', $container, 'test_parameter', [ 'four', 'five', 'six' ]);
		
		// Check the parameter contains the merged array
		$this->assertEquals([ 'one', 'two', 'three', 'four', 'five', 'six' ], $container->getParameter('test_parameter'));
	}
	
	/**
	 * @covers ::prepend()
	 * @testdox prepend() prepends framework.secret
	 */
	public function test_prepend_secret()
	{
		// Override kernel name
		self::$container->setParameter('kernel.name', 'boot');
		
		// Prepend the extension config
		self::$extension->prepend(self::$container);
		
		// Check the secret option is set
		$config = call_user_func_array('array_merge', self::$container->getExtensionConfig('framework'));
		$this->assertEquals('$ecret', $config['secret']);
	}
	
	/**
	 * @covers ::prepend()
	 * @testdox prepend() prepends assets.base_path
	 */
	public function test_prepend_assets_base_path()
	{
		// Prepend the extension config
		self::$extension->prepend(self::$container);
		
		// Check the assets.base_path option is set
		$this->assertEquals([
			[
				'assets' => [
					'base_path' => '..',
				],
			],
			[
				'secret' => '$ecret',
			]
		], self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::prepend()
	 * @testdox prepend() does not prepend assets.base_path when assets.base_url is set
	 */
	public function test_prepend_no_assets_base_path_with_base_url()
	{
		// Prepend a faked extension config with assets.base_url set
		self::$container->prependExtensionConfig('framework', [
			'assets' => [
				'base_url' => '//localhost/web'
			]
		]);
		
		// Prepend the extension config
		self::$extension->prepend(self::$container);
		
		// Check no assets.base_path option is set
		$this->assertEquals([
			[
				'secret' => '$ecret',
			],
			[
				'assets' => [
					'base_url' => '//localhost/web'
				]
			],
		], self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::prepend()
	 * @testdox prepend() prepends assets.packages.*.base_path
	 */
	public function test_prepend_prepends_package_assets_base_path()
	{
		// Prepend a faked extension config with assets packages
		self::$container->prependExtensionConfig('framework', [
			'assets' => [
				'packages' => [
					'default' => []
				],
			]
		]);
		
		// Prepend the extension config
		self::$extension->prepend(self::$container);
		
		// Check the assets.packages.*.base_path and assets.base_path options are set
		$this->assertEquals([
			[
				'assets' => [
					'packages' => [
						'default' => [
							'base_path' => '..',
						],
					],
				],
			],
			[
				'assets' => [
					'base_path' => '..',
				],
			],
			[
				'secret' => '$ecret',
			],
			[
				'assets' => [
					'packages' => [
						'default' => [],
					],
				],
			]
		], self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::prepend()
	 * @testdox prepend() does not prepend assets.packages.*.base_path when assets.packages.*.base_url is set
	 */
	public function test_prepend_prepends_no_package_assets_base_path_with_base_url()
	{
		// Prepend a faked config with assets.packages.*.base_url set
		self::$container->prependExtensionConfig('framework', [
			'assets' => [
				'packages' => [
					'default' => [
						'base_url' => '//localhost/web'
					]
				],
			]
		]);
		
		// Prepend the extension config
		self::$extension->prepend(self::$container);

		// Check the assets.base_path option is set and the assets.packages.*.base_path option is not
		$this->assertEquals([
			[
				'assets' => [
					'base_path' => '..',
				],
			],
			[
				'secret' => '$ecret',
			],
			[
				'assets' => [
					'packages' => [
						'default' => [
							'base_url' => '//localhost/web'
						]
					],
				]
			]
		], self::$container->getExtensionConfig('framework'));
	}
	
	/**
	 * @covers ::load()
	 * @covers ::registerClassCacheConfiguration()
	 * @covers ::registerCommandsConfiguration()
	 * @testdox load() without configuration loads default configuration
	 */
	public function test_load_without_configuration()
	{
		self::$container->loadFromExtension(self::$extension->getAlias());
		self::$container->compile();
		
		// Check the motana.multikernel.default parameter exists and is null
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.default'));
		
		// Check the motana.multikernel.default parameter contains NULL
		$this->assertNull(self::$container->getParameter('motana.multikernel.default'));
		
		// Check the motana.multikernel.class_cache.exclude parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.class_cache.exclude'));
		
		// Check the motana.multikernel.class_cache.exclude parameter contains the correct class names
		$this->assertEquals(self::$classes['exclude'], self::$container->getParameter('motana.multikernel.class_cache.exclude'));
		
		// Check the motana.multikernel.commands.add parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.add'));
		
		// Check the motana.multikernel.commands.add parameter contains the correct command names
		$this->assertEquals(self::$commands['add'], self::$container->getParameter('motana.multikernel.commands.add'));
		
		// Check the motana.multikernel.commands.global parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.global'));
		
		// Check the motana.multikernel.commands.global parameter contains the correct command names
		$this->assertEquals(self::$commands['global'], self::$container->getParameter('motana.multikernel.commands.global'));
	
		// Check the motana.multikernel.commands.hidden parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.hidden'));
		
		// Check the motana.multikernel.commands.hidden parameter contains the correct command names
		$this->assertEquals(self::$commands['hidden'], self::$container->getParameter('motana.multikernel.commands.hidden'));
	}

	/**
	 * @covers ::load()
	 * @covers ::registerClassCacheConfiguration()
	 * @covers ::registerCommandsConfiguration()
	 * @testdox load() with disabled configuration has no default kernel name
	 */
	public function test_load_disabled_configuration()
	{
		// Generate the configuration file for the test
		$generator = new FixtureGenerator();
		$generator->setSkeletonDirs(__DIR__ . '/../../src/Resources/fixtures');
		$generator->generateConfig('config/config.' . self::$format . '.twig', self::$fixturesDir . '/disabled.' . self::$format, [
			'kernel' => 'null',
		]);
		
		// Load the configuration and compile the container
		$this->loadConfiguration(self::$container, 'disabled');
		self::$container->compile();
		
		// Check the motana.multikernel.default parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.default'));
		
		// Check the motana.multikernel.default parameter contains NULL
		$this->assertNull(self::$container->getParameter('motana.multikernel.default'));
		
		// Check the motana.multikernel.class_cache.exclude is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.class_cache.exclude'));
		
		// Check the motana.multikernel.class_cache.exclude contains the correct class names
		$this->assertEquals(self::$classes['exclude'], self::$container->getParameter('motana.multikernel.class_cache.exclude'));
		
		// Check the motana.multikernel.commands.add parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.add'));
		
		// Check the motana.multikernel.commands.add parameter contains the correct command names
		$this->assertEquals(self::$commands['add'], self::$container->getParameter('motana.multikernel.commands.add'));
		
		// Check the motana.multikernel.commands.global parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.global'));
		
		// Check the motana.multikernel.commands.global parameter contains the correct command names
		$this->assertEquals(self::$commands['global'], self::$container->getParameter('motana.multikernel.commands.global'));
		
		// Check the motana.multikernel.commands.hidden parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.hidden'));
		
		// Check the motana.multikernel.commands.hidden parameter contains the correct command names
		$this->assertEquals(self::$commands['hidden'], self::$container->getParameter('motana.multikernel.commands.hidden'));
	}
	
	/**
	 * @covers ::load()
	 * @covers ::registerClassCacheConfiguration()
	 * @covers ::registerCommandsConfiguration()
	 * @testdox load() with enabled configuration has default kernel name
	 */
	public function test_load_enabled_configuration()
	{
		// Generate the configuration file for the test
		$generator = new FixtureGenerator();
		$generator->setSkeletonDirs(__DIR__ . '/../../src/Resources/fixtures');
		$generator->generateConfig('config/config.' . self::$format . '.twig', self::$fixturesDir . '/enabled.' . self::$format, [
			'kernel' => 'app',
		]);
		
		// Load the configuration and compile the container
		$this->loadConfiguration(self::$container, 'enabled');
		self::$container->compile();
		
		// Check the motana.multikernel.default parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.default'));
		
		// Check the motana.multikernel.default parameter contains the correct kernel name
		$this->assertEquals('app', self::$container->getParameter('motana.multikernel.default'));
		
		// Check the motana.multikernel.class_cache.exclude is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.class_cache.exclude'));
		
		// Check the motana.multikernel.class_cache.exclude contains the correct class names
		$this->assertEquals(self::$classes['exclude'], self::$container->getParameter('motana.multikernel.class_cache.exclude'));
		
		// Check the motana.multikernel.commands.add parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.add'));
		
		// Check the motana.multikernel.commands.add parameter contains the correct command names
		$this->assertEquals(self::$commands['add'], self::$container->getParameter('motana.multikernel.commands.add'));
		
		// Check the motana.multikernel.commands.global parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.global'));
		
		// Check the motana.multikernel.commands.global parameter contains the correct command names
		$this->assertEquals(self::$commands['global'], self::$container->getParameter('motana.multikernel.commands.global'));
		
		// Check the motana.multikernel.commands.hidden parameter is set
		$this->assertTrue(self::$container->hasParameter('motana.multikernel.commands.hidden'));
		
		// Check the motana.multikernel.commands.hidden parameter contains the correct command names
		$this->assertEquals(self::$commands['hidden'], self::$container->getParameter('motana.multikernel.commands.hidden'));
	}
	
	/**
	 * @covers ::getXsdValidationBasePath()
	 * @testdox getXsdValidationBasePath() returns the correct path
	 */
	public function test_getXsdValidationBasePath()
	{
		$class = new \ReflectionClass(self::$extension);
		$expected = dirname($class->getFileName()) . '/../Resources/config/schema';
		
		// Check the expected path is returned
		$this->assertEquals($expected, self::$extension->getXsdValidationBasePath());
		
		// Check the returned path exists
		$this->assertTrue(is_dir($expected));
		
		// Check the motana-multikernel-1.0.xsd exists in the returned path
		$this->assertTrue(is_file($expected . '/motana-multikernel-1.0.xsd'));
	}
	
	/**
	 * @covers ::getNamespace()
	 * @testdox getNamespace() returns the correct namespace
	 */
	public function test_getNamespace()
	{
		// Check the expected namespace is returned
		$this->assertEquals('http://symfony.com/schema/dic/motana-multikernel', self::$extension->getNamespace());
	}
}
