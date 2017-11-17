<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Generator;

use Motana\Bundle\MultikernelBundle\Generator\AppGenerator;
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Generator\AppGenerator
 * @testdox Motana\Bundle\MultikernelBundle\Generator\AppGenerator
 */
class AppGeneratorTest extends TestCase
{
	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static $fixturesDir;
	
	/**
	 * Output of the manipulator.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;

	/**
	 * Skeleton directories for the generator.
	 *
	 * @var array
	 */
	protected static $skeletonDirs;
	
	/**
	 * Generator used for the tests.
	 *
	 * @var AppGenerator
	 */
	protected static $generator;
	
	/**
	 * @beforeClass
	 */
	public static function setUpTestEnvironment()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set the fixtures directory for the tests
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/generator/app';
		
		// Override the output of the generators
		self::$output = new BufferedOutput();
		self::writeAttribute(Generator::class, 'output', self::$output);
		
		// Get the skeleton dirs
		$class = new \ReflectionClass(SensioGeneratorBundle::class);
		$path = dirname($class->getFileName());
		self::$skeletonDirs = [
			$path . '/Resources/skeleton',
			$path . '/Resources',
			__DIR__ . '/../../src/Resources/skeleton',
			__DIR__ . '/../../src/Resources',
		];
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$generator = new AppGenerator();
		self::$generator->setSkeletonDirs(self::$skeletonDirs);
	}

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown()
	{
		self::getFs()->remove(self::$fixturesDir);
	}
	
	/**
	 * Generates an app model for the tests.
	 *
	 * @param boolean $generateBundle Boolean indicating to generate a bundle
	 * @param boolean $generateMicrokernel Boolean indicating to generate a microkernel
	 * @param string $bundleConfigFormat Bundle configuration format
	 * @return \Motana\Bundle\MultikernelBundle\Generator\Model\App
	 */
	protected function generateAppModel($generateBundle, $generateMicrokernel = false, $bundleConfigFormat = 'annotation')
	{
		$bundleName = ($generateMicrokernel ? 'Micro' : 'App') . 'Bundle';
		return new App(
			self::$fixturesDir,
			$generateMicrokernel ? 'micro' : 'app',
			false,
			$generateBundle,
			$generateBundle ? $bundleName : null,
			$generateBundle ? $bundleName: null,
			$generateBundle ? self::$fixturesDir . '/src' : null,
			$generateBundle ? $bundleConfigFormat : null,
			$generateMicrokernel
		);
	}
	
	/**
	 * Assert that an application environment exists in the specified directory.
	 *
	 * @param string $dir Directory name
	 * @param App $app App model for the app to generate
	 */
	public static function assertAppExists($dir, App $app)
	{
		// Check the .htaccess was generated
		self::assertFileExists(sprintf('%s/%s/.htaccess', $dir, $app->getKernelName(), $app->getCacheClassName()));
		
		// Check the app kernel files were created
		if ( ! $app->shouldGenerateMicrokernel()) {
			self::assertFileExists(sprintf('%s/%s/%s.php', $dir, $app->getKernelName(), $app->getCacheClassName()));
		}
		self::assertFileExists(sprintf('%s/%s/%s.php', $dir, $app->getKernelName(), $app->getKernelClassName()));
		
		// Check the app configuration files were generated
		if ($app->shouldGenerateMicrokernel()) {
			self::assertFileExists(sprintf('%s/%s/config/config.yml', $dir, $app->getKernelName()));
		} else {
			self::assertFileExists(sprintf('%s/%s/config/config.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/config_dev.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/config_prod.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/config_test.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/parameters.yml.dist', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/routing.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/routing_dev.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/security.yml', $dir, $app->getKernelName()));
			self::assertFileExists(sprintf('%s/%s/config/services.yml', $dir, $app->getKernelName()));
		}
		
		// Check the app bundle files were generated
		if ($app->shouldGenerateBundle())
		{
			// Check the app template files were generated
			if ($app->shouldGenerateMicrokernel()) {
				self::assertFileExists(sprintf('%s/%s/Resources/views/random.html.twig', $dir, $app->getKernelName()));
			} else {
				self::assertFileExists(sprintf('%s/%s/Resources/views/base.html.twig', $dir, $app->getKernelName()));
				self::assertFileExists(sprintf('%s/%s/Resources/views/default/index.html.twig', $dir, $app->getKernelName()));
			}
			
			// Check the Bundle and Controller classes were generated
			self::assertFileExists(sprintf('%s/src/%s/%s.php', $dir, $app->getNamespace(), $app->getName()));
			if ($app->shouldGenerateMicrokernel()) {
				self::assertFileExists(sprintf('%s/src/%s/Controller/MicroController.php', $dir, $app->getNamespace()));
			} else {
				self::assertFileExists(sprintf('%s/src/%s/Controller/DefaultController.php', $dir, $app->getNamespace()));
			}
			
			if ($app->shouldGenerateMicrokernel()) {
				// Check the test files were generated
				self::assertFileExists(sprintf('%s/%s/Controller/MicroControllerTest.php', $app->getTestsDirectory(), $app->getNamespace()));
			} else {
				// Check the services configuration was generated
				self::assertFileExists(sprintf('%s/src/%s/Resources/config/%s', $dir, $app->getNamespace(), $app->getServicesConfigurationFilename()));
				
				// Check the routing configuration was generated
				if ($routingFilename = $app->getRoutingConfigurationFilename()) {
					self::assertFileExists(sprintf('%s/src/%s/Resources/config/%s', $dir, $app->getNamespace(), $routingFilename));
				}
	
				// Check the test files were generated
				self::assertFileExists(sprintf('%s/%s/Controller/DefaultControllerTest.php', $app->getTestsDirectory(), $app->getNamespace()));
			}
		}
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Check the filesystem property has been initialized correctly
		$this->assertInstanceOf(Filesystem::class, $this->readAttribute(self::$generator, 'filesystem'));
	}
	
	/**
	 * @covers ::generateApp()
	 * @testdox generateApp() generates an application without bundle
	 */
	public function test_generateApp()
	{
		// Generate the app
		$model = $this->generateAppModel(false);
		self::$generator->generateApp($model, [
			'bundles' => []
		]);
		
		// Check the expected files were generated
		$this->assertAppExists(self::$fixturesDir, $model);
	}
	
	/**
	 * Data provider for test_generateApp_with_bundle().
	 *
	 * @return array
	 */
	public function provide_test_generateApp_with_bundle_data()
	{
		return [
			'annotation format' => [
				'annotation',
				null,
				null
			],
			'php format' => [
				'php',
				null,
				null
			],
			'xml format' => [
				'xml',
				null,
				null
			],
			'yml format' => [
				'yml',
				null,
				null
			],
		];
	}
	
	/**
	 * @covers ::generateApp()
	 * @dataProvider provide_test_generateApp_with_bundle_data
	 * @param string $bundleConfigFormat Bundle configuration format
	 * @testdox generateApp() generates an application with bundle configuration in
	 */
	public function test_generateApp_with_bundle($bundleConfigFormat)
	{
		// Generate the app
		$model = $this->generateAppModel(true, false, $bundleConfigFormat);
		self::$generator->generateApp($model, [
			'bundles' => []
		]);
		
		// Check the expected files were generated
		$this->assertAppExists(self::$fixturesDir, $model);
	}
	
	/**
	 * @covers ::generateApp()
	 * @testdox generateApp() generates an application with microkernel and no bundle
	 */
	public function test_generateApp_with_microkernel()
	{
		$model = $this->generateAppModel(false, true);
		self::$generator->generateApp($model, [
			'bundles' => []
		]);
		
		// Check the expected files were generated
		$this->assertAppExists(self::$fixturesDir, $model);
	}
	
	/**
	 * @covers ::generateApp()
	 * @testdox generateApp() generates an application with microkernel and bundle
	 */
	public function test_generateApp_with_microkernel_and_bundle()
	{
		$model = $this->generateAppModel(true, true);
		self::$generator->generateApp($model, [
			'bundles' => []
		]);
		
		// Check the expected files were generated
		$this->assertAppExists(self::$fixturesDir, $model);
	}
}
