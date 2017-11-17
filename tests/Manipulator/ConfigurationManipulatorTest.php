<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Manipulator;

use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\Manipulator\ConfigurationManipulator;
use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Manipulator\ConfigurationManipulator
 * @testdox Motana\Bundle\MultikernelBundle\Manipulator\ConfigurationManipulator
 */
class ConfigurationManipulatorTest extends TestCase
{
	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static $fixturesDir;
	
	/**
	 * The manipulator to test.
	 *
	 * @var ConfigurationManipulator
	 */
	protected static $manipulator;
	
	/**
	 * Output of the manipulator.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * File currently in use for a test.
	 *
	 * @var string
	 */
	protected $file;
	
	/**
	 * @beforeClass
	 */
	public static function setUpFixtureFiles()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set the fixtures directory for the tests
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/manipulator/config';
		
		// Parameters for twig templates
		$parameters = [
			'kernel_name' => 'app',
			'namespace' => 'AppBundle',
			'bundle' => true,
			'bundle_path' => 'src/AppBundle',
			'bundles' => [
				'DoctrineBundle' => false,
				'MotanaMultikernelBundle' => false,
				'SwiftmailerBundle' => false,
			],
		];
		
		// Set the output of the Generator classes to a buffered output
		self::$output = new BufferedOutput();
		self::writeAttribute(Generator::class, 'output', self::$output);
		self::writeAttribute(FilesystemManipulator::class, 'output', self::$output);
		
		// Set the output of the Generator class to a buffered output
		$generator = new FixtureGenerator();
		$generator->generateConfig('app/config.yml.twig', self::$fixturesDir . '/config.yml', $parameters);
		$generator->generateConfig('app/config_dev.yml.twig', self::$fixturesDir . '/config_dev.yml', $parameters);
		$generator->generateConfig('app/services.yml.twig', self::$fixturesDir . '/services.yml', $parameters);
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($file = null)
	{
		// Load the requested file
		if (null !== $file) {
			$this->file = self::$fixturesDir . '/' . $file;
			self::$manipulator = new ConfigurationManipulator($this->file);
		}
		
		// Clear output
		self::$output->fetch();
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Load the config.yml for the test
		$this->setUp('config.yml');
		
		// Generate expected results
		$content = file_get_contents($this->file);
		$config = Yaml::parse($content);
		
		// Check the properties are initialized correctly
		$this->assertEquals($this->file, $this->readAttribute(self::$manipulator, 'file'));
		$this->assertEquals($content, $this->readAttribute(self::$manipulator, 'content'));
		$this->assertEquals($content, $this->readAttribute(self::$manipulator, 'processedContent'));
		$this->assertEquals($config, $this->readAttribute(self::$manipulator, 'config'));
	}
	
	/**
	 * Data provider for test_configureApp().
	 *
	 * @return array
	 */
	public function provide_test_configureApp_data()
	{
		return [
			'config.yml' => [
				null,
				null,
				null
			],
			'config_dev.yml' => [
				'dev',
				null,
				null
			]
		];
	}
	
	/**
	 * @covers ::updateAppConfiguration()
	 * @dataProvider provide_test_configureApp_data
	 * @param string $environment Environment to run a test for
	 * @testdox updateAppConfiguration() makes correct modifications in
	 */
	public function test_updateAppConfiguration($environment)
	{
		// Determine the short name of the file and the routing resource filename
		$file = 'config' . ($environment ? '_' . $environment : '') . '.yml';
		$routingFile = 'routing' . ($environment ? '_' . $environment : '') . '.yml';
		
		// Load the file for the test
		self::setUp($file);
		
		// Update the configuration
		$this->callMethod(self::$manipulator, 'updateAppConfiguration', $environment);
		
		// Get the configuration
		$config = Yaml::parse($this->readAttribute(self::$manipulator, 'processedContent'));
		
		// Check the router resource path modification is correct
		if (isset($config['framework']['router']['resource'])) {
			$this->assertEquals('%kernel.project_dir%/apps/%kernel.name%/config/' . $routingFile, $config['framework']['router']['resource']);
		}
		
		// Check the session save path modification is correct
		if (isset($config['framework']['session']['save_path'])) {
			$this->assertEquals('%kernel.project_dir%/var/sessions/%kernel.name%/%kernel.environment%', $config['framework']['session']['save_path']);
		}
	}
	
	/**
	 * @covers ::updateServiceConfiguration()
	 * @testdox updateServiceConfiguration() makes correct modifications in services.yml
	 */
	public function test_updateServiceConfiguration()
	{
		// Load config.yml for the test
		self::setUp('services.yml');
		
		// Update the configuration
		$this->callMethod(self::$manipulator, 'updateServiceConfiguration');
		
		// Get the configuration
		$config = Yaml::parse($this->readAttribute(self::$manipulator, 'processedContent'));
		
		// Check the services configuration modification is correct
		if (isset($config['services'])) {
			foreach ($config['services'] as $classOrNamespace => $section) {
				if (isset($section['resource']) && false !== strpos($section['resource'], '/src')) {
					$this->assertContains('%kernel.project_dir%/src', $section['resource']);
				}
				if (isset($section['exclude']) && false !== strpos($section['exclude'], '/src')) {
					$this->assertContains('%kernel.project_dir%/src', $section['exclude']);
				}
			}
		}
	}
	
	/**
	 * Data provider for test_updateConfigurationForMultikernel().
	 *
	 * @return array
	 */
	public function provide_test_updateConfigurationForMultikernel_data()
	{
		return [
			'non-existing file' => [
				'non-existing.yml',
				null,
				null
			],
			'config.yml' => [
				'config.yml',
				null,
				null
			],
			'config_dev.yml' => [
				'config_dev.yml',
				null,
				null
			],
			'services.yml' => [
				'services.yml',
				null,
				null
			],
		];
	}
	
	/**
	 * @covers ::updateConfigurationForMultikernel()
	 * @dataProvider provide_test_updateConfigurationForMultikernel_data
	 * @param string $file File basename
	 * @testdox updateConfigurationForMultikernel() returns correct results for
	 */
	public function test_updateConfigurationForMultikernel($file)
	{
		// Load the file for the test
		$this->setUp($file);
		
		// Detect the environment the file is for
		$environment = null;
		if (false !== strpos($file, '_')) {
			$parts = explode('_', basename($file, '.yml'));
			$shortName = current($parts);
			$environment = end($parts);
		}
		
		// Update the configuration
		$result = self::$manipulator->updateConfigurationForMultikernel();

		// Check the output is correct
		if ('non-existing.yml' !== $file) {
			$this->assertEquals('  updated ' . self::$fixturesDir . '/' . $file . "\n", self::$output->fetch());
		}
		
		// Get the configuration
		$config = Yaml::parse($this->readAttribute(self::$manipulator, 'processedContent'));
		
		// Check the configuration modification is correct
		switch ($file) {
			// Check the return value is FALSE for non-existing files
			case 'not-existing.yml':
				$this->assertFalse($result);
				break;
			
			// Check the router resource and session save path modifications are correct
			case 'config.yml':
			case 'config_dev.yml':
				$this->assertTrue($result);
				$routingFile = 'routing' . ($environment ? '_' . $environment : '') . '.yml';
				if (isset($config['framework']['router']['resource'])) {
					$this->assertEquals('%kernel.project_dir%/apps/%kernel.name%/config/' . $routingFile, $config['framework']['router']['resource']);
				}
				if (isset($config['framework']['session']['save_path'])) {
					$this->assertEquals('%kernel.project_dir%/var/sessions/%kernel.name%/%kernel.environment%', $config['framework']['session']['save_path']);
				}
				break;
				
			// Check the services configuration modification is correct
			case 'services.yml':
				$this->assertTrue($result);
				if (isset($config['services'])) {
					foreach ($config['services'] as $classOrNamespace => $section) {
						if (isset($section['resource']) && false !== strpos($section['resource'], '/src')) {
							$this->assertContains('%kernel.project_dir%/src', $section['resource']);
						}
						if (isset($section['exclude']) && false !== strpos($section['exclude'], '/src')) {
							$this->assertContains('%kernel.project_dir%/src', $section['exclude']);
						}
					}
				}
				break;
		}
	}
}
