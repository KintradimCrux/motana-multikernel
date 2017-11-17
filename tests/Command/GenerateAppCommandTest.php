<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Command\GenerateAppCommand;
use Motana\Bundle\MultikernelBundle\Generator\AppGenerator;
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\InteractiveCommandTestCase;

use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\GenerateAppCommand
 * @kernelDir generate_app
 * @testdox Motana\Bundle\MultikernelBundle\Command\GenerateAppCommand
 */
class GenerateAppCommandTest extends InteractiveCommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'generate:app', [ 'command_name' => 'generate:app' ]);
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/generate_app';
		
		// Adjust the autoloader psr-4 fallback dirs
		$loader = current(current(spl_autoload_functions()));
		/** @var ClassLoader $loader */
		self::writeAttribute($loader, 'fallbackDirsPsr4', array_merge(self::readAttribute($loader, 'fallbackDirsPsr4'), [
			self::$fixturesDir . '/src',
		]));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\InteractiveCommandTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		// Call parent method
		parent::setUp($app, $environment, $debug);
		
		// Override the output properties of the Generator and FilesystemManipulator classes
		$this->writeAttribute(Generator::class, 'output', self::$output);
		$this->writeAttribute(FilesystemManipulator::class, 'output', self::$output);
	}
	
	/**
	 * @covers ::configure()
	 * @testdox configure() sets up the command correctly
	 */
	public function test_configure()
	{
		// Configure the command
		$this->callMethod(self::$command, 'configure');
		
		// Check the command name was set correctly
		$this->assertEquals('generate:app', self::$command->getName());
		
		// Check the command description was set correctly
		$this->assertEquals('Generates an app', self::$command->getDescription());
		
		// Get the input definition of the command
		$definition = self::$command->getNativeDefinition();
		$arguments = array_values($definition->getArguments());
		$options = array_values($definition->getOptions());
		
		// Check there are no arguments
		$this->assertEquals(0, count($arguments));
		
		// Check there are 5 options
		$this->assertEquals(5, count($options));
		
		// Check the options are set correctly
		$this->assertInputOption($options[0], InputOption::class, 'kernel', null, InputOption::VALUE_REQUIRED, 'App kernel name');
		$this->assertInputOption($options[1], InputOption::class, 'dir', null, InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', 'src/');
		$this->assertInputOption($options[2], InputOption::class, 'format', null, InputOption::VALUE_REQUIRED, 'Use the format for bundle configuration files (php, xml, yml, or annotation)');
		$this->assertInputOption($options[3], InputOption::class, 'micro', null, InputOption::VALUE_NONE, 'Generate a microkernel app');
		$this->assertInputOption($options[4], InputOption::class, 'no-bundle', null, InputOption::VALUE_NONE, 'Skip generating a bundle for the app');
		
		// Check the commandline help is correct
		$this->assertEquals(<<<EOH
The <info>%command.name%</info> command helps you generates new apps.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--kernel</comment> is the only one needed if you follow the
conventions):

<info>php %command.full_name% --kernel=app</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php %command.full_name% --kernel=foo --dir=src [--no-bundle] --no-interaction</info>

The names of the generated kernel, cache and bundle class names are generated from the camelized kernel name.

EOH
		, self::$command->getHelp());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @kernelEnvironment dev
	 * @testdox isEnabled() returns TRUE in a multikernel dev environment
	 */
	public function test_isEnabled_returns_TRUE_multikernel_dev()
	{
		// Set up the environment to test
		$this->setUp(null, 'dev');
		
		// Check the command is enabled in the expected environments
		$this->assertTrue(self::$command->isEnabled());
	}

	/**
	 * @covers ::isEnabled()
	 * @testdox isEnabled() returns TRUE in a multikernel test environment
	 */
	public function test_isEnabled_returns_TRUE_multikernel_test()
	{
		// Set up the environment to test
		$this->setUp(null, 'test');
		
		// Check the command is enabled in the expected environments
		$this->assertTrue(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "generate:app" does not exist.
	 * @kernelEnvironment prod
	 * @testdox isEnabled() returns FALSE in a multikernel prod environment
	 */
	public function test_isEnabled_returns_FALSE_multikernel_prod()
	{
		// Check the command is disabled in all but the expected environments
		$this->setUp(null, 'prod');
	}
	
	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "generate:app" does not exist.
	 * @kernelEnvironment dev
	 * @testdox isEnabled() returns FALSE in an appkernel dev environment
	 */
	public function test_isEnabled_returns_FALSE_appkernel_dev()
	{
		// Check the command is disabled in all but the expected environments
		$this->setUp('generate_app_app', 'dev');
	}
	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "generate:app" does not exist.
	 * @testdox isEnabled() returns FALSE in an appkernel test environment
	 */
	public function test_isEnabled_returns_FALSE_appkernel_test()
	{
		// Check the command is disabled in all but the expected environments
		$this->setUp('generate_app_app', 'test');
	}

	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "generate:app" does not exist.
	 * @kernelEnvironment prod
	 * @testdox isEnabled() returns FALSE in an appkernel prod environment
	 */
	public function test_isEnabled_returns_FALSE_appkernel_prod()
	{
		// Check the command is disabled in all but the expected environments
		$this->setUp('generate_app_app', 'prod');
	}
	
	/**
	 * @covers ::getQuestionHelper()
	 * @testdox getQuestionHelper() returns an instance of the correct class
	 */
	public function test_getQuestionHelper()
	{
		// Get the question helper
		$question = $this->callMethod(self::$command, 'getQuestionHelper');
		
		// Check the helper is an instance of the correct class
		$this->assertInstanceOf(QuestionHelper::class, $question);
	}
	
	/**
	 * @covers ::createAppObject()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "kernel" option must be provided.
	 * @testdox createAppObject() checks that all required options are specified
	 */
	public function test_createAppObject_checks_options()
	{
		// Create an input with options for the method call
		$input = new ArrayInput([
			'--dir' => 'src',
		]);
		$input->bind(self::$command->getDefinition());
		
		// Call the method
		$this->callMethod(self::$command, 'createAppObject', $input);
	}

	/**
	 * @covers ::createAppObject()
	 * @testdox createAppObject() returns an App object with correct values
	 */
	public function test_createAppObject()
	{
		// Create an input with options for the method call
		$input = new ArrayInput([
			'--dir' => 'src',
			'--kernel' => 'foo',
			'--format' => 'annotation',
		]);
		$input->bind(self::$command->getDefinition());
		
		// Call the method
		$app = $this->callMethod(self::$command, 'createAppObject', $input);
		/** @var App $app */
		
		// Check the app object contains correct bundle options
		$this->assertEquals('FooBundle', $app->getNamespace());
		$this->assertEquals('FooBundle', $app->getName());
		$this->assertEquals(self::$fixturesDir . '/src/FooBundle', $app->getTargetDirectory());
		$this->assertEquals('annotation', $app->getConfigurationFormat());
		$this->assertFalse($app->isShared());
		$this->assertEquals(self::$fixturesDir . '/tests', $app->getTestsDirectory());
		
		// Check the app object contains correct app options
		$this->assertEquals(self::$fixturesDir, $app->getProjectDirectory());
		$this->assertEquals('foo', $app->getKernelName());
		$this->assertTrue($app->shouldGenerateBundle());
		$this->assertFalse($app->shouldGenerateMicrokernel());
		$this->assertTrue($app->shouldGenerateMultikernel());
	}
	
	/**
	 * @covers ::createGenerator()
	 * @testdox createGenerator() returns an AppGenerator instance
	 */
	public function test_createGenerator()
	{
		$this->assertInstanceOf(AppGenerator::class, $this->callMethod(self::$command, 'createGenerator'));
	}
	
	/**
	 * @covers ::getSkeletonDirs()
	 * @testdox getSkeletonDirs() returns the correct template paths
	 */
	public function test_getSkeletonDirs()
	{
		// Get the path of the GeneratorCommand class
		$class = new \ReflectionClass(GeneratorCommand::class);
		$sensioBundlePath = dirname($class->getFileName());
		
		// Get the path of the GenerateAppCommand class
		$class = new \ReflectionClass(self::$command);
		$commandPath = dirname($class->getFileName());
		
		// Check the returned skeleton dirs are correct
		$this->assertEquals([
			$sensioBundlePath . '/../Resources/skeleton',
			$sensioBundlePath . '/../Resources',
			$commandPath . '/../Resources/skeleton',
			$commandPath . '/../Resources',
		], $this->callMethod(self::$command, 'getSkeletonDirs'));
	}
	
	/**
	 * @covers ::getSkeletonDirs()
	 * @testdox getSkeletonDirs() returns the correct template paths for a bundle
	 */
	public function test_getSkeletonDirs_with_bundle()
	{
		// Get the GenerateAppBundle
		$bundle = new \GenerateAppAppBundle\GenerateAppAppBundle();
		$bundlePath = $bundle->getPath();
		$kernelPath = self::$application->getKernel()->getRootDir();
		
		// Create dummy skeleton directories for the test
		self::getFs()->mkdir($bundlePath . '/Resources/MotanaMultikernelBundle/skeleton');
		self::getFs()->mkdir($kernelPath . '/Resources/MotanaMultikernelBundle/skeleton');
		
		// Get the path of the GeneratorCommand class
		$class = new \ReflectionClass(GeneratorCommand::class);
		$sensioBundlePath = dirname($class->getFileName());
		
		// Get the path of the GenerateAppCommand class
		$class = new \ReflectionClass(self::$command);
		$commandPath = dirname($class->getFileName());
		
		// Check the returned skeleton dirs are correct
		$this->assertEquals([
			$sensioBundlePath . '/../Resources/skeleton',
			$sensioBundlePath . '/../Resources',
			$bundlePath . '/Resources/MotanaMultikernelBundle/skeleton',
			$kernelPath . '/Resources/MotanaMultikernelBundle/skeleton',
			$commandPath . '/../Resources/skeleton',
			$commandPath . '/../Resources',
		], $this->callMethod(self::$command, 'getSkeletonDirs', $bundle));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\InteractiveCommandTestCase::provide_test_interact_data()
	 */
	public function provide_test_interact_data()
	{
		$options = [
			'kernel' => 'foo',
			'dir' => 'src/',
			'format' => '',
			'micro' => false,
			'no-bundle' => false,
			'help' => false,
			'quiet' => false,
			'verbose' => false,
			'version' => false,
			'ansi' => false,
			'no-ansi' => false,
			'no-interaction' => false,
			'env' => 'test',
			'no-debug' => false,
		];
		
		return [
			'an invalid kernel name' => [
				null,
				'kernel_invalid',
				[
					'$foo',
					'foo',
					'',
					'',
					'',
				],
				array_merge($options, [
					'format' => 'annotation',
				]),
			],
			'an invalid format' => [
				null,
				'format_invalid',
				[
					'foo',
					'',
					'csv',
					'',
					'',
				],
				array_merge($options, [
					'format' => 'annotation',
				]),
			],
			'an absolute path' => [
				null,
				'directory_absolute',
				[
					'foo',
					'/tmp',
					'',
					'',
					'',
				],
				array_merge($options, [
					'format' => 'annotation',
				])
			],
			'a path containing \'../\'' => [
				null,
				'directory_traversal',
				[
					'foo',
					'../',
					'',
					'',
					'',
				],
				array_merge($options, [
					'format' => 'annotation',
				])
			],
			'a valid kernel name (annotation format)' => [
				null,
				'format_annotation',
				[
					'foo',
					'',
					'annotation',
					'',
				],
				array_merge($options, [
					'format' => 'annotation',
				]),
			],
			'a valid kernel name (php format)' => [
				null,
				'format_php',
				[
					'foo',
					'',
					'php',
					'',
				],
				array_merge($options, [
					'format' => 'php',
				]),
			],
			'a valid kernel name (xml format)' => [
				null,
				'format_xml',
				[
					'foo',
					'',
					'xml',
					'',
				],
				array_merge($options, [
					'format' => 'xml',
				]),
			],
			'a valid kernel name (yml format)' => [
				null,
				'format_yml',
				[
					'foo',
					'',
					'yml',
					'',
				],
				array_merge($options, [
					'format' => 'yml',
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
	 * @testdox interact() returns correct output for
	 */
	public function test_interact($app, $template, array $interactiveInput = [], array $expectedOptions = [])
	{
		return parent::test_interact($app, $template, $interactiveInput, $expectedOptions);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::provide_test_execute_data()
	 */
	public function provide_test_execute_data()
	{
		return [
			'standard app with bundle (annotation)' => [
				null,
				'std_bundle_annotation',
				[
					'--kernel' => 'generate_app_exec_annot',
					'--dir' => 'src/',
					'--format' => 'annotation',
				]
			],
			'standard app with bundle (php)' => [
				null,
				'std_bundle_php',
				[
					'--kernel' => 'generate_app_exec_php',
					'--dir' => 'src/',
					'--format' => 'php',
				]
			],
			'standard app with bundle (xml)' => [
				null,
				'std_bundle_xml',
				[
					'--kernel' => 'generate_app_exec_xml',
					'--dir' => 'src/',
					'--format' => 'xml',
				]
			],
			'standard app with bundle (yml)' => [
				null,
				'std_bundle_yml',
				[
					'--kernel' => 'generate_app_exec_yml',
					'--dir' => 'src/',
					'--format' => 'yml',
				]
			],
			'standard app without bundle' => [
				null, 'std_no_bundle',
				[
					'--kernel' => 'generate_app_exec_no_bundle',
					'--dir' => 'src/',
					'--format' => 'annotation',
					'--no-bundle' => true,
				]
			],
			'microkernel app with bundle (annotation)' => [
				null,
				'micro_bundle_annotation',
				[
					'--kernel' => 'generate_app_exec_micro_annot',
					'--dir' => 'src/',
					'--format' => 'annotation',
				]
			],
			'microkernel app with bundle (php)' => [
				null,
				'micro_bundle_php',
				[
					'--kernel' => 'generate_app_exec_micro_php',
					'--dir' => 'src/',
					'--format' => 'php',
				]
			],
			'microkernel app with bundle (xml)' => [
				null,
				'micro_bundle_xml',
				[
					'--kernel' => 'generate_app_exec_micro_xml',
					'--dir' => 'src/',
					'--format' => 'xml',
				]
			],
			'microkernel app with bundle (yml)' => [
				null,
				'micro_bundle_yml',
				[
					'--kernel' => 'generate_app_exec_micro_yml',
					'--dir' => 'src/',
					'--format' => 'yml',
				]
			],
			'microkernel app without bundle' => [
				null,
				'micro_no_bundle',
				[
					'--kernel' => 'generate_app_exec_micro_no_bundle',
					'--dir' => 'src/',
					'--format' => 'annotation',
				]
			],
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() returns correct output and generates a
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		// Call parent method
		$return = parent::test_execute($app, $template, $parameters);

		// Check the app has been generated
		$this->assertAppExists(self::$fixturesDir, $parameters['--kernel'], $parameters['--format'], empty($parameters['--no-bundle']));
		
		// Remove the generated app
		self::getFs()->remove(self::$fixturesDir . '/apps/' . $parameters['--kernel']);
		
		// Return the return value
		return $return;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::provide_test_run_data()
	 */
	public function provide_test_run_data()
	{
		$interactiveInput = [
			'',
			'',
			'',
			'',
		];
		
		return [
			'standard app with bundle (annotation)' => [
				null,
				'std_bundle_annotation',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_annot',
					'--dir' => 'src/',
					'--format' => 'annotation',
				],
				$interactiveInput
			],
			'standard app with bundle (php)' => [
				null,
				'std_bundle_php',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_php',
					'--dir' => 'src/',
					'--format' => 'php',
				],
				$interactiveInput
			],
			'standard app with bundle (xml)' => [
				null,
				'std_bundle_xml',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_xml',
					'--dir' => 'src/',
					'--format' => 'annotation',
				],
				$interactiveInput
			],
			'standard app with bundle (yml)' => [
				null,
				'std_bundle_yml',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_yml',
					'--dir' => 'src/',
					'--format' => 'annotation',
				],
				$interactiveInput
			],
			'standard app without bundle' => [
				null,
				'std_no_bundle',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_no_bundle',
					'--dir' => 'src/',
					'--format' => 'annotation',
					'--no-bundle' => true,
				],
				$interactiveInput
			],
			'microkernel app with bundle (annotation)' => [
				null,
				'micro_bundle_annotation',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_micro_annot',
					'--dir' => 'src/',
					'--format' => 'annotation',
				],
				$interactiveInput
			],
			'microkernel app with bundle (php)' => [
				null,
				'micro_bundle_php',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_micro_php',
					'--dir' => 'src/',
					'--format' => 'php',
				],
				$interactiveInput
			],
			'microkernel app with bundle (xml)' => [
				null,
				'micro_bundle_xml',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_micro_xml',
					'--dir' => 'src/',
					'--format' => 'xml',
				],
				$interactiveInput
			],
			'microkernel app with bundle (yml)' => [
				null,
				'micro_bundle_yml',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_micro_yml',
					'--dir' => 'src/',
					'--format' => 'yml',
				],
				$interactiveInput
			],
			'microkernel app without bundle' => [
				null,
				'micro_no_bundle',
				[
					'kernel' => null,
					'--kernel' => 'generate_app_run_micro_no_bundle',
					'--dir' => 'src/',
					'--format' => 'annotation',
					'--no-bundle' => true,
				],
				$interactiveInput
			],
		];
	}
	
	/**
	 * @covers ::run()
	 * @covers ::execute()
	 * @dataProvider provide_test_run_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @param array $interactiveInput Interactive input
	 * @testdox run() returns correct output and generates a
	 */
	public function test_run($app, $template, array $parameters = [], array $interactiveInput = [])
	{
		// Call parent method
		$return = parent::test_run($app, $template, $parameters, $interactiveInput);
		
		// Check the app has been generated
		$this->assertAppExists(self::$fixturesDir, $parameters['--kernel'], $parameters['--format'], empty($parameters['--no-bundle']));
		
		// Remove the generated app
		self::getFs()->remove(self::$fixturesDir . '/apps/' . $parameters['--kernel']);
		
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
		// Command name parameter
		unset($parameters['command_name']);
		
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
	 * Assert that an application exists in the specified directory.
	 *
	 * @param string $dir Directory name
	 * @param string $kernelName Kernel name
	 * @param string $format Bundle configuration format
	 * @param boolean $generateBundle Boolean indicating to generate a bundle
	 * @return void
	 */
	public static function assertAppExists($dir, $kernelName, $format, $generateBundle)
	{
		// Use Yaml configuration format for route annotations
		$routingConfig = true;
		if ($format == 'annotation') {
			$routingConfig = false;
			$format = 'yml';
		}
		
		// Get the directory and kernel name prefixes
		$envPrefix = basename($dir);
		$envPrefixCamel = BootKernel::camelizeKernelName($envPrefix);
		$kernelDir = 'default' !== $envPrefix ? $kernelName : 'app';
		$kernelCamel = BootKernel::camelizeKernelName($kernelName);
		
		// Check that composer.json was created
		self::assertFileExists(sprintf('%s/composer.json', $dir));
		
		// Check that bin/console was created
		self::assertFileExists(sprintf('%s/bin/console', $dir));
		
		// Check the front controller files were created
		self::assertFileExists(sprintf('%s/web/app.php', $dir));
		self::assertFileExists(sprintf('%s/web/app_dev.php', $dir));
		
		// Check the boot kernel files were created
		self::assertFileExists(sprintf('%s/apps/.htaccess', $dir));
		self::assertFileExists(sprintf('%s/apps/autoload.php', $dir));
		self::assertFileExists(sprintf('%s/apps/%sBootKernel.php', $dir, $envPrefixCamel));
		
		// Check the boot kernel configuration files were created
		self::assertDirectoryExists(sprintf('%s/apps/config', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config_dev.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config_prod.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config_test.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/parameters.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/parameters.yml.dist', $dir));
		
		// Check the app kernel files were created
		self::assertFileExists(sprintf('%s/apps/%s/%sCache.php', $dir, $kernelDir, $kernelCamel));
		self::assertFileExists(sprintf('%s/apps/%s/%sKernel.php', $dir, $kernelDir, $kernelCamel));
		
		// Check the app configuration files were created
		self::assertFileExists(sprintf('%s/apps/%s/config/config.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/config_dev.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/config_prod.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/config_test.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/parameters.yml.dist', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/routing.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/routing_dev.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/security.yml', $dir, $kernelDir));
		self::assertFileExists(sprintf('%s/apps/%s/config/services.yml', $dir, $kernelDir));
		
		// A bundle should be generated for the app
		if ($generateBundle)
		{
			// Check the app template files were created
			self::assertFileExists(sprintf('%s/apps/%s/Resources/views/base.html.twig', $dir, $kernelDir));
			self::assertFileExists(sprintf('%s/apps/%s/Resources/views/default/index.html.twig', $dir, $kernelDir));
			
			// Check the app bundle files were created
			self::assertFileExists(sprintf('%s/src/%sBundle/%sBundle.php', $dir, $kernelCamel, $kernelCamel));
			self::assertFileExists(sprintf('%s/src/%sBundle/Controller/DefaultController.php', $dir, $kernelCamel));
			if ($routingConfig) {
				self::assertFileExists(sprintf('%s/src/%sBundle/Resources/config/routing.%s', $dir, $kernelCamel, $format));
			}
			self::assertFileExists(sprintf('%s/src/%sBundle/Resources/config/services.%s', $dir, $kernelCamel, $format));
			
			// Check the test files were created
			self::assertFileExists(sprintf('%s/tests/%sBundle/Controller/DefaultControllerTest.php', $dir, $kernelCamel));
		}
	}
}
