<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests;

use Motana\Bundle\MultikernelBundle\Generator\AppGenerator;
use Motana\Bundle\MultikernelBundle\Generator\BootKernelGenerator;
use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Listener for PHPUnit for creating a test environment and pre-generating containers.
 *
 * When starting the root test suite, a test environment is created under /tmp to run
 * the tests in.
 *
 * To speed up tests, it also reads annnotations from the docblock comments of the
 * test class and each test method and boots the kernel required for a test, before
 * PHPUnit starts collecting code coverage.
 *
 * When the --debug commandline option is specified, the listener will output a
 * message when the test environment is generated or removed, or kernels are booted.
 *
 * When the --verbose commandline option also is specified, the listener will output
 * a detailed list of what files are generated or removed.
 *
 * The following annotations are supported:
 *
 * @kernelDir <subdirectory>
 *     switches to a different subdirectory of the test environment
 *     default is 'project'
 *
 * @kernelEnvironment <environment>
 *     sets the $environment parameter for the kernel constructor
 *
 * @kernelDebug <true|false>
 *     sets the $debug parameter for the kernel
 *
 * @kernelBoot true
 *     force booting the kernels
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class TestListener extends \PHPUnit_Framework_BaseTestListener
{
	/**
	 * Default annotation values.
	 *
	 * @var array
	 */
	private static $defaults = [
		'kernelDir' => 'default',
		'kernelEnvironment' => 'test',
		'kernelDebug' => false,
		'kernelBoot' => false,
	];

	/**
	 * Boolean indicating that the listener runs in a child process.
	 *
	 * @var boolean
	 */
	private $isChild;

	/**
	 * Base directory of the fixtures generated for the tests.
	 *
	 * @var string
	 */
	private $fixtureBaseDir;

	/**
	 * Boolean indicating the last subdirectory of the test environment a kernel was booted in.
	 *
	 * @var boolean
	 */
	private $kernelDir;

	/**
	 * The last environment a kernel was booted for.
	 *
	 * @var string
	 */
	private $kernelEnvironment;

	/**
	 * Whether to enable debugging or not.
	 *
	 * @var boolean
	 */
	private $kernelDebug;

	/**
	 * Boolean indicating that testdox output should be generated.
	 *
	 * @var boolean
	 */
	private $testdox = false;

	/**
	 * Boolean indicating to output messages from the generators and manipulators.
	 *
	 * @var string
	 */
	private $debug = false;

	/**
	 * Boolean indicating to output verbose messages from the generators and manipulators.
	 *
	 * @var boolean
	 */
	private $verbose = false;

	/**
	 * Current depth into the test suite tree, 0 = root.
	 *
	 * @var integer
	 */
	private $depth = 0;

	/**
	 * Filesystem manipulator object.
	 *
	 * @var FilesystemManipulator
	 */
	private $fs;

	/**
	 * Buffered output for messages from generators and manipulators.
	 *
	 * @var ConsoleOutput
	 */
	private $output;

	/**
	 * Generator for a boot kernel.
	 *
	 * @var BootKernelGenerator
	 */
	private $kernelGenerator;

	/**
	 * Generator for an app.
	 *
	 * @var AppGenerator
	 */
	private $appGenerator;

	/**
	 * Generator for fixture files.
	 *
	 * @var FixtureGenerator
	 */
	private $fixtureGenerator;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		// Check if this  is a child process
		$this->isChild = false !== ($this->fixtureBaseDir = getenv('__MULTIKERNEL_FIXTURE_DIR'));

		// Create a temporary directory for the test environment, if not running in a child process
		if ( ! $this->isChild) {
			$this->fixtureBaseDir = tempnam(sys_get_temp_dir(), 'motana_multikernel_tests_');
			(new Filesystem())->remove($this->fixtureBaseDir);
			putenv('__MULTIKERNEL_FIXTURE_DIR=' . $this->fixtureBaseDir);
		}

		// Testdox mode is enabled if the --testdox
		$this->testdox = in_array('--testdox', $_SERVER['argv']);

		// Debug mode is enabled if the --debug option is specified and the --testdox option is not
		$this->debug = in_array('--debug', $_SERVER['argv']) && ! $this->testdox;

		// Verbose mode is enabled if the -v or --verbose option is specified and debug mode is enabled
		$this->verbose = $this->debug && (in_array('-v', $_SERVER['argv']) || in_array('--verbose', $_SERVER['argv']));

		// Create a new FS manipulator and buffered output
		$this->fs = new FilesystemManipulator();
		$this->output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, false);

		// Create the required generator instances
		$this->kernelGenerator = new BootKernelGenerator();
		$this->kernelGenerator->setSkeletonDirs(__DIR__ . '/../src/Resources/skeleton');

		$this->appGenerator = new AppGenerator();
		$this->appGenerator->setSkeletonDirs([
			__DIR__ . '/../src/Resources/skeleton',
			__DIR__ . '/..' . ( ! is_dir(__DIR__ . '/../vendor') ? '/../..' : '') . '/vendor/sensio/generator-bundle/Resources/skeleton',
		]);

		$this->fixtureGenerator = new FixtureGenerator();
	}

	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_BaseTestListener::startTestSuite()
	 */
	public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
	{
		// Create fixture directories only when the root test suite starts
		if (0 === $this->depth++ && ! $this->isChild)
		{
			// Always print the headline, except for testdox output
			if ( ! $this->testdox) {
				$this->output->writeln(sprintf('Generating test environment in %s', $this->fixtureBaseDir));
			}
			if ( ! $this->testdox && ! $this->debug && ! $this->verbose) {
				$this->output->writeln('');
			}

			// Determine which output to use for Generator and Manipulator messages
			$output = $this->verbose ? $this->output : new NullOutput();

			// Catch output of the generators and manipulators
			TestCase::writeAttribute(Generator::class, 'output', $output);
			TestCase::writeAttribute(FilesystemManipulator::class, 'output', $output);

			// Generate a broken environment for the tests requiring it
			$this->generateBrokenKernelEnvironment();

			// Generate the default test environment for tests
			$this->generateKernelEnvironment();

			// Generate test environments for several commands
			$this->generateKernelEnvironment('generate_app');
			$this->generateKernelEnvironment('generate_bundle'); // To be removed when https://github.com/sensiolabs/SensioGeneratorBundle/issues/568 is resolved
			$this->generateKernelEnvironment('multikernel_convert', false);
			$this->generateKernelEnvironment('multikernel_convert_functional', false);
			$this->generateKernelEnvironment('multikernel_convert_run', false);

			// Reset the output of the generators and manipulators
			TestCase::writeAttribute(Generator::class, 'output', null);
			TestCase::writeAttribute(FilesystemManipulator::class, 'output', null);

			// Print the footer for verbose mode
			if ($this->verbose) {
				$this->output->writeln('Finished generating test environment.');
			}
		}

		// Do not process data provider test suites
		if ($suite instanceof \PHPUnit_Framework_TestSuite_DataProvider) {
			return;
		}

		// Do not boot any kernels for tests running in isolation
		if (function_exists('__phpunit_run_isolated_test')) {
			return;
		}

		// Suite name is a class name
		if (class_exists($suite->getName(), false))
		{
			// Read the required environment from annotations
			$options = $this->parseAnnotations($annot = \PHPUnit_Util_Test::parseTestMethodAnnotations($suite->getName()));

			// Boot the kernels for the detected environment
			$this->bootKernels(
				$options['kernelDir'],
				$options['kernelEnvironment'],
				$options['kernelDebug'],
				$options['kernelBoot']
			);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_BaseTestListener::startTest()
	 */
	public function startTest(\PHPUnit_Framework_Test $test)
	{
		// Do not process incomplete, skipped or warning test cases
		if (
		   $test instanceof \PHPUnit_Framework_IncompleteTestCase
		|| $test instanceof \PHPUnit_Framework_SkippedTestCase
		|| $test instanceof \PHPUnit_Framework_WarningTestCase
		) {
			return;
		}

		// Do not boot any kernels in isolation
		if (function_exists('__phpunit_run_isolated_test')) {
			return;
		}

		// Read the required environment from annotations
		$options = $this->parseAnnotations($annot = \PHPUnit_Util_Test::parseTestMethodAnnotations(get_class($test), $test->getName(false)));

		// Boot the kernels for the detected environment
		$this->bootKernels(
			$options['kernelDir'],
			$options['kernelEnvironment'],
			$options['kernelDebug'],
			$options['kernelBoot']
		);
	}

	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_BaseTestListener::endTestSuite()
	 */
	public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
	{
		// Remove fixture directories only when the root test suite finished
		if (0 < --$this->depth || $this->isChild) {
			return;
		}

		// Print the headline
		if ( ! $this->testdox) {
			$this->output->writeln([ '', '', sprintf('Removing test environment in %s', $this->fixtureBaseDir) ]);
		}

		// Determine which output to use for Generator and Manipulator messages
		$output = $this->verbose ? $this->output : new NullOutput();

		// Catch output of the generators and manipulators
		TestCase::writeAttribute(Generator::class, 'output', $output);
		TestCase::writeAttribute(FilesystemManipulator::class, 'output', $output);

		// Remove the temporary directory
		$this->fs->remove($this->fixtureBaseDir);

		// Reset the output of the generators and manipulators
		TestCase::writeAttribute(Generator::class, 'output', null);
		TestCase::writeAttribute(FilesystemManipulator::class, 'output', null);

		// Print the footer for verbose mode
		if ($this->verbose) {
			$this->output->writeln('Finished removing test environment.');
		}
	}

	/**
	 * Boot the kernels for a specified environment.
	 *
	 * @param string $dir Subdirectory of the test enviroment to use
	 * @param string $environment The environment
	 * @param boolean $debug Whether to enable debugging or not
	 * @param boolean $force Force booting the kernels
	 * @return void
	 */
	private function bootKernels($dir, $environment, $debug, $force)
	{
		// No action required if the environment, debug and broken parameters did not change
		if ( ! $force && $dir === $this->kernelDir && $this->kernelEnvironment === $environment && $this->kernelDebug === $debug) {
			return;
		}

		// Update properties
		$this->kernelDir = $dir;
		$this->kernelDebug = $debug;
		$this->kernelEnvironment = $environment;

		// Get the test directory
		$projectPath = $this->fixtureBaseDir . '/' . $dir;

		// No multikernel environment found
		if ( ! is_dir($projectPath . '/apps'))
		{
			// Find Symfony Standard app kernels in the directory
			$files = iterator_to_array(
				Finder::create()
				->files()
				->depth(1)
				->name('*Kernel.php')
				->in($projectPath)
			);

			// Get the class names of the kernels
			$kernels = array_map(function(SplFileInfo $file) {
				return $file->getBasename('.php');
			}, $files);
		}

		// Multikernel environment found
		else
		{
			// Determine the class name and path of the boot kernel to load
			$kernelPrefix = 'default' !== $dir ? Container::camelize($dir) : '';
			$className = $kernelPrefix .'BootKernel';
			$kernels = [ $projectPath . '/apps/' . $className .'.php' => $className ];
		}

		// Print a message showing what happens
		if ($this->debug) {
			echo PHP_EOL . 'Booting kernels: dir=' . $dir . ', environment=' . $environment . ', debug=' . ($debug ? 'true' : 'false') . PHP_EOL;
		}

		// Adjust the autoloader psr-4 fallback dirs
		$loader = current(current(spl_autoload_functions()));
		/** @var ClassLoader $loader */
		TestCase::writeAttribute($loader, 'fallbackDirsPsr4', array_merge(TestCase::readAttribute($loader, 'fallbackDirsPsr4'), [
			$projectPath . '/src',
		]));

		// Process each of the kernels
		foreach ($kernels as $classPath => $className)
		{
			// Print the class name in debug mode
			if ($this->verbose) {
				echo '  ' . $className . PHP_EOL;
			}

			// Load the class
			if ( ! class_exists($className)) {
				require_once($classPath);
			}

			// Boot the kernel
			$kernel = new $className($environment, $debug);
			$kernel->boot();

			// BootKernel environment without broken kernels
			if ($kernel instanceof BootKernel && 'broken' !== $dir)
			{
				// Boot the attached application kernels
				foreach ($kernel->getKernels() as $appkernel) {
					if ( ! $appkernel instanceof BootKernel) {
						if ($this->verbose) {
							echo '    ' . get_class($appkernel) . PHP_EOL;
						}
						$appkernel->boot();
					}
				}
			}
		}

		// Restore the autoloader psr-4 fallback dirs
		$srcDir = $projectPath . '/src';
		TestCase::writeAttribute($loader, 'fallbackDirsPsr4', array_filter(TestCase::readAttribute($loader, 'fallbackDirsPsr4'), function($dir) use ($srcDir) {
			return $dir !== $srcDir;
		}));
	}

	/**
	 * Generate a test environment for the kernel test cases requiring a working kernel.
	 *
	 * @param string $dir Subdirectory name
	 * @param boolean $multikernel Boolean indicating to create a multikernel app
	 * @return void
	 */
	private function generateKernelEnvironment($dir = 'default', $multikernel = true)
	{
		$kernelPrefix = 'default' !== $dir ? $dir . '_' : '';
		$classPrefix = 'default' !== $dir ? Container::camelize($dir) : '';

		$dir = $this->fixtureBaseDir . '/' . $dir;

		// Generate the boot kernel
		if ($multikernel) {
			$this->kernelGenerator->generateBootkernel([ $kernelPrefix . 'app' ], $dir, $classPrefix . 'BootKernel');
		}

		// Generate the app kernel
		$this->appGenerator->generateApp(new App(
			$dir,
			$kernelPrefix . 'app',
			$multikernel,
			true,
			$classPrefix . 'AppBundle',
			$classPrefix . 'AppBundle',
			$dir . '/src',
			'annotation',
			false
		), [
			'bundles' => [
				'DoctrineBundle' => false,
				'SensioDistributionBundle' => false,
				'SensioFrameworkExtraBundle' => true,
				'SwiftmailerBundle' => false,
			]
		]);

		// Generate a parameters.yml for the app
		$this->fixtureGenerator->generateConfig('config/parameters.yml.twig', $dir . ($multikernel ? '/apps' : '') . '/' . $kernelPrefix . 'app/config/parameters.yml');

		// Create a dummy composer.json
		$this->fixtureGenerator->generateConfig('config/composer.json.twig', $dir . '/composer.json');
	}

	/**
	 * Generate a test environment for the tests requiring a broken project filesystem.
	 *
	 * @return void
	 */
	private function generateBrokenKernelEnvironment()
	{
		$dir = $this->fixtureBaseDir . '/broken';

		// Generate the boot kernel
		$this->kernelGenerator->generateBootkernel([ 'app' ], $dir, 'BrokenBootKernel');

		// Generate a config for the broken environment
		$this->fixtureGenerator->generateConfig('config/config_broken.yml.twig', $dir . '/apps/config/config_broken.yml');

		// Generate an app directory without any content
		$this->fs->mkdir($dir . '/apps/broken_app');

		// Create a second app directory that contains a working kernel but a cache class with invalid name
		$this->fixtureGenerator->generateCacheClass($dir . '/apps/broken_cache/BrokenCacheCache.php', 'InvalidCache');
		$this->fixtureGenerator->generateEmptyKernelClass($dir . '/apps/broken_cache/BrokenCacheKernel.php', 'BrokenCacheKernel');

		// Create a third app directory that contains a working cache class but a kernel class with invalid name
		$this->fixtureGenerator->generateCacheClass($dir . '/apps/broken_kernel/BrokenKernelCache.php', 'BrokenKernelCache');
		$this->fixtureGenerator->generateEmptyKernelClass($dir . '/apps/broken_kernel/BrokenKernelKernel.php', 'InvalidKernel');

		// Create a dummy composer.json
		$this->fs->dumpFile($dir . '/composer.json', '{}');
	}

	/**
	 * Parse the annotations in a docblock comment.
	 * Returns a boolean indicating whether options have changed or not.
	 *
	 * @param array $annotations Annotations to parse
	 * @return boolean
	 */
	private function parseAnnotations(array $annotations)
	{
		// Merge the annotations and reduce the to a single value
		$annotations = array_map('current', call_user_func_array('array_merge', $annotations));

		// Parse the annotations
		$options = [];
		foreach (array_keys(self::$defaults) as $option)
		{
			// Get the option value from either an annotation or the defaults
			$value = isset($annotations[$option]) ? $annotations[$option] : self::$defaults[$option];

			// Convert boolean options
			if ( ! in_array($option, [ 'kernelDir', 'kernelEnvironment' ]) && is_string($value)) {
				$value = in_array($value, [ 'true', 'on', 'enabled' ]);
			}

			// Set the option value
			$options[$option] = $value;
		}

		// Return the parsed annotations
		return $options;
	}
}
