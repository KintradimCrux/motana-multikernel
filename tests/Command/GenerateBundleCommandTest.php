<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\InteractiveCommandTestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Model\Bundle;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\GenerateBundleCommand
 * @kernelDir generate_bundle
 * @kernelEnvironment dev
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 * @testdox Motana\Bundle\MultikernelBundle\Command\GenerateBundleCommand
 */
class GenerateBundleCommandTest extends InteractiveCommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'generate:bundle', []);
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
		
		// Set the fixtures directory for the tests
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/generate_bundle';
		
		// Adjust the autoloader psr-4 fallback dirs
		$loader = current(current(spl_autoload_functions()));
		/** @var ClassLoader $loader */
		self::writeAttribute($loader, 'fallbackDirsPsr4', array_merge(self::readAttribute($loader, 'fallbackDirsPsr4'), [
			self::$fixturesDir . '/src',
		]));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'dev', $debug = false)
	{
		// Set up the application and command instances if an app has been specified
		if (null !== $app) {
			parent::setUp($app, $environment, $debug);
		}
		
		// Override the output of Generator and FilesystemManipulator
		$this->writeAttribute(Generator::class, 'output', self::$output);
		$this->writeAttribute(FilesystemManipulator::class, 'output', self::$output);
	}
	
	/**
	 * @covers ::isEnabled()
	 * @testdox isEnabled() returns TRUE when SensioGeneratorBundle is loaded
	 */
	public function test_isEnabled()
	{
		// Set up the app environment
		$this->setUp('generate_bundle_app');
		
		// Check the command is enabled
		$this->assertTrue(self::$command->isEnabled());
	}

	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "generate:bundle" does not exist.
	 * @kernelEnvironment prod
	 * @testdox isEnabled() returns FALSE when SensioGeneratorBundle is not loaded
	 */
	public function test_isEnabled_without_SensioGeneratorBundle()
	{
		// Set up the app environment
		$this->setUp('generate_bundle_app', 'prod');
	}

	/**
	 * @covers ::createBundleObject()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "namespace" option must be provided.
	 * @testdox createBundleObject() checks that all required options are specified
	 */
	public function test_createBundleObject_checks_options()
	{
		// Set up the app environment
		$this->setUp('generate_bundle_app');
		
		// Create an input with options for the method call
		$input = new ArrayInput([
			'--dir' => 'src',
		]);
		$input->bind(self::$command->getDefinition());
		
		// Call the method
		$this->callMethod(self::$command, 'createBundleObject', $input);
	}
	
	/**
	 * @covers ::createBundleObject()
	 * @testdox createBundleObject() returns a Bundle object with correct values
	 */
	public function test_createBundleObject()
	{
		// Set up the app environment
		$this->setUp('generate_bundle_app');
		
		// Create an input with options for the method call
		$input = new ArrayInput([
			'--namespace' => 'TestBundle',
			'--dir' => 'src',
		]);
		$input->bind(self::$command->getDefinition());
		
		// Call the method
		$bundle = $this->callMethod(self::$command, 'createBundleObject', $input);
		/** @var Bundle $bundle */
		
		// Check the bundle object contains correct values
		$this->assertEquals('TestBundle', $bundle->getNamespace());
		$this->assertEquals('TestBundle', $bundle->getName());
		$this->assertEquals(self::$fixturesDir . '/src/TestBundle', $bundle->getTargetDirectory());
		$this->assertEquals('annotation', $bundle->getConfigurationFormat());
		$this->assertFalse($bundle->isShared());
		$this->assertEquals(self::$fixturesDir . '/tests/TestBundle', $bundle->getTestsDirectory());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\InteractiveCommandTestCase::provide_test_interact_data()
	 */
	public function provide_test_interact_data()
	{
		$options = [
			'namespace' => '',
			'dir' => 'src/',
			'bundle-name' => '',
			'format' => 'annotation',
			'shared' => false,
			'help' => false,
			'quiet' => false,
			'verbose' => false,
			'version' => false,
			'ansi' => false,
			'no-ansi' => false,
			'no-interaction' => false,
			'env' => 'dev',
			'no-debug' => false,
		];
		
		return [
			'simple bundle' => [
				'generate_bundle_app',
				'simple_bundle',
				[
					'no',
					'BlogBundle',
					'',
					'',
					'',
				],
				array_merge($options, [
					'namespace' => 'BlogBundle',
					'bundle-name' => 'BlogBundle',
				]),
			],
			'simple bundle with namespace' => [
				'generate_bundle_app',
				'simple_bundle_ns',
				[
					'no',
					'Acme/BlogBundle',
					'AcmeBlogBundle',
					'',
					'',
					'',
				],
				array_merge($options, [
					'namespace' => 'Acme\\BlogBundle',
					'bundle-name' => 'AcmeBlogBundle',
				]),
			],
			'shared bundle' => [
				'generate_bundle_app',
				'shared_bundle',
				[
					'yes',
					'Acme/BlogBundle',
					'AcmeBlogBundle',
					'',
					'',
					'',
				],
				array_merge($options, [
					'namespace' => 'Acme\\BlogBundle',
					'bundle-name' => 'AcmeBlogBundle',
					'shared' => true,
					'format' => 'xml',
				]),
			],
			'simple bundle with invalid name' => [
				'generate_bundle_app',
				'invalid_simple_bundle',
				[
					'no',
					'1BlogBundle',
					'BlogBundle',
					'',
					'',
					'',
				],
				array_merge($options, [
					'namespace' => 'BlogBundle',
					'bundle-name' => 'BlogBundle',
				]),
			],
			'simple bundle with namespace and invalid name' => [
				'generate_bundle_app',
				'invalid_simple_bundle_ns',
				[
					'no',
					'1Acme/1BlogBundle',
					'Acme/BlogBundle',
					'AcmeBlogBundle',
					'',
					'',
					'',
				],
				array_merge($options, [
					'namespace' => 'Acme\\BlogBundle',
					'bundle-name' => 'AcmeBlogBundle',
				]),
			],
			'shared bundle with invalid name' => [
				'generate_bundle_app',
				'invalid_shared_bundle',
				[
					'yes',
					'1Acme/1BlogBundle',
					'Acme/BlogBundle',
					'AcmeBlogBundle',
					'',
					'',
					'',
				],
				array_merge($options, [
					'namespace' => 'Acme\\BlogBundle',
					'bundle-name' => 'AcmeBlogBundle',
					'shared' => true,
					'format' => 'xml',
				]),
			],
		];
	}

	/**
	 * @covers ::interact()
	 * @dataProvider provide_test_interact_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $interactiveInput Interactive input
	 * @param array $expectedOptions Expected input options after interaction
	 * @testdox interact() returns correct output for a
	 */
	public function test_interact($app, $template, array $interactiveInput = [], array $expectedOptions = [])
	{
		parent::test_interact($app, $template, $interactiveInput, $expectedOptions);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::provide_test_execute_data()
	 */
	public function provide_test_execute_data()
	{
		return [
			'simple bundle (annotation)' => [
				'generate_bundle_app',
				'simple_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'ExecAnnotBundle',
					'--bundle-name' => 'ExecAnnotBundle',
					'--config-format' => 'annotation',
				]
			],
			'simple bundle (php)' => [
				'generate_bundle_app',
				'simple_bundle',
				[
				'--dir' => 'src',
				'--namespace' => 'ExecPhpBundle',
				'--bundle-name' => 'ExecPhpBundle',
				'--config-format' => 'php',
				]
			],
			'simple bundle (xml)' => [
				'generate_bundle_app',
				'simple_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'ExecXmlBundle',
					'--bundle-name' => 'ExecXmlBundle',
					'--config-format' => 'xml',
				]
			],
			'simple bundle (yml)' => [
				'generate_bundle_app',
				'simple_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'ExecYmlBundle',
					'--bundle-name' => 'ExecYmlBundle',
					'--config-format' => 'yml',
				]
			],
			'simple bundle with namespace (annotation)' => [
				'generate_bundle_app',
				'simple_bundle_ns',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Simple/AnnotBundle',
					'--bundle-name' => 'ExecSimpleAnnotBundle',
					'--config-format' => 'annotation',
				]
			],
			'simple bundle with namespace (php)' => [
				'generate_bundle_app',
				'simple_bundle_ns',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Simple/PhpBundle',
					'--bundle-name' => 'ExecSimplePhpBundle',
					'--config-format' => 'php',
				]
			],
			'simple bundle with namespace (xml)' => [
				'generate_bundle_app',
				'simple_bundle_ns',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Simple/XmlBundle',
					'--bundle-name' => 'ExecSimpleXmlBundle',
					'--config-format' => 'xml',
				]
			],
			'simple bundle with namespace (yml)' => [
				'generate_bundle_app',
				'simple_bundle_ns',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Simple/YmlBundle',
					'--bundle-name' => 'ExecSimpleYmlBundle',
					'--config-format' => 'yml',
				]
			],
			'shared bundle (annotation)' => [
				'generate_bundle_app',
				'shared_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Shared/AnnotBundle',
					'--bundle-name' => 'ExecSharedAnnotBundle',
					'--config-format' => 'annotation',
					'--shared' => true,
				]
			],
			'shared bundle (php)' => [
				'generate_bundle_app',
				'shared_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Shared/PhpBundle',
					'--bundle-name' => 'ExecSharedPhpBundle',
					'--config-format' => 'php',
					'--shared' => true,
				]
			],
			'shared bundle (xml)' => [
				'generate_bundle_app',
				'shared_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Shared/XmlBundle',
					'--bundle-name' => 'ExecSharedXmlBundle',
					'--config-format' => 'xml',
					'--shared' => true,
				]
			],
			'shared bundle (yml)' => [
				'generate_bundle_app',
				'shared_bundle',
				[
					'--dir' => 'src',
					'--namespace' => 'Exec/Shared/YmlBundle',
					'--bundle-name' => 'ExecSharedYmlBundle',
					'--config-format' => 'yml',
					'--shared' => true,
				]
			],
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_data
	 * @testdox execute() returns correct output and generates a
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		// Call parent method
		$return = parent::test_execute($app, $template, $parameters);

		// Check the bundle was generated
		$this->assertBundleExists(self::$fixturesDir, $parameters['--namespace'], $parameters['--bundle-name'], $parameters['--config-format'], ! empty($parameters['--shared']));
		
		// Return the return value
		return $return;
	}

	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\InteractiveCommandTestCase::provide_test_run_data()
	 */
	public function provide_test_run_data()
	{
		$interactiveInput = [
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
		];
		
		return array_map(function(array $data) use ($interactiveInput) {
			$data[2]['--namespace'] = str_replace('Exec', 'Run', $data[2]['--namespace']);
			$data[2]['--bundle-name'] = str_replace('Exec', 'Run', $data[2]['--bundle-name']);
			$data[] = $interactiveInput;
			$data[3][0] = strpos($data[1], 'shared') === 0 ? 'yes' : 'no';
			return $data;
		}, $this->provide_test_execute_data());
	}

	/**
	 * @covers ::run()
	 * @dataProvider provide_test_run_data
	 * @testdox run() returns correct output and generates a
	 */
	public function test_run($app, $template, array $parameters = [], array $interactiveInput = [])
	{
		// Call parent method
		$return = parent::test_run($app, $template, $parameters, $interactiveInput);
		
		// Check the bundle was generated
		$this->assertBundleExists(self::$fixturesDir, $parameters['--namespace'], $parameters['--bundle-name'], $parameters['--config-format'], ! empty($parameters['--shared']));
		
		// Return the return value
		return $return;
	}
	
	/**
	 * Filters input command parameters before binding input.
	 *
	 * @param array $parameters Parameters to filter
	 * @return array
	 */
	protected static function filterCommandParameters(array $parameters = [])
	{
		// Format parameter
		if (isset($parameters['--config-format'])) {
			$parameters['--format'] = $parameters['--config-format'];
			unset($parameters['--config-format']);
		}
		
		// Return the input
		return $parameters;
	}
	
	/**
	 * Convert input parameters to display options for the template.
	 *
	 * @param array $parameters Parameters to convert
	 * @return array
	 */
	protected static function convertParametersToOptions(array $parameters = [])
	{
		// Start with no options
		$options = [];
		
		// Format option
		if (isset($parameters['--config-format']) && in_array($parameters['--config-format'], ['anotation', 'php', 'xml', 'yml'])) {
			$options[$parameters['--config-format']] = $parameters['--config-format'];
		}
		
		// Return the input
		return $options;
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format
	 * @param string $commandName Command name
	 * @param boolean $generateTemplates Boolean indicating to generate templates
	 * @return string
	 */
	protected static function getTemplate($case, array $options = [], $format, $commandName, $generateTemplates = false)
	{
		return parent::getTemplate($case, $options, $format, $commandName, $generateTemplates);
	}
	
	/**
	 * Assert that a multikernel application environment exists in the specified directory.
	 *
	 * @param string $dir Directory name
	 * @param string $bundleNamespace Bundle namespace
	 * @param string $bundleName Bundle name
	 * @param string $format Bundle configuration format
	 * @param boolean $shared Boolean indicating that a shared bundle should be there
	 * @return void
	 */
	public static function assertBundleExists($dir, $bundleNamespace, $bundleName, $format, $shared)
	{
		// Use Yaml configuration format for route annotations
		$routingConfig = true;
		if ($format == 'annotation') {
			$routingConfig = false;
			$format = 'yml';
		}
		
		// Check the app template files were created
		self::assertFileExists(sprintf('%s/apps/generate_bundle_app/Resources/views/base.html.twig', $dir));
		self::assertFileExists(sprintf('%s/apps/generate_bundle_app/Resources/views/default/index.html.twig', $dir));
		
		// Check the app bundle files were created
		self::assertFileExists(sprintf('%s/src/%s/%s.php', $dir, $bundleNamespace, $bundleName));
		self::assertFileExists(sprintf('%s/src/%s/Controller/DefaultController.php', $dir, $bundleNamespace));
		if ($routingConfig) {
			self::assertFileExists(sprintf('%s/src/%s/Resources/config/routing.%s', $dir, $bundleNamespace, $format));
		}
		self::assertFileExists(sprintf('%s/src/%s/Resources/config/services.%s', $dir, $bundleNamespace, $format));
		
		// Check the test files were created
		if ($shared) {
			self::assertFileExists(sprintf('%s/src/%s/Tests/Controller/DefaultControllerTest.php', $dir, $bundleNamespace));
		} else {
			self::assertFileExists(sprintf('%s/tests/%s/Controller/DefaultControllerTest.php', $dir, $bundleName));
		}
	}
}
