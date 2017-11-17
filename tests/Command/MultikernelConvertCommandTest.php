<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Generator\BootKernelGenerator;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase;
use Motana\Bundle\MultikernelBundle\Tests\Generator\BootKernelGeneratorTest;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\MultikernelConvertCommand
 * @kernelDir multikernel_convert
 * @kernelEnvironment dev
 * @testdox Motana\Bundle\MultikernelBundle\Command\MultikernelConvertCommand
 */
class MultikernelConvertCommandTest extends CommandTestCase
{
	/**
	 * Content of composer.json before running test_updateComposerJson().
	 *
	 * @var string
	 */
	protected static $composerJson;
	
	/**
	 * Constructor.
	 *
	 * @param string $name Test name
	 * @param array $data Test dataset
	 * @param string $dataName Test dataset name
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'multikernel:convert', [ '--format' => 'txt' ]);
	}
	
	/**
	 * Set up the test environment before the tests run.
	 *
	 * @beforeClass
	 * @param string $dir Subdirectory of the test envionment to use
	 * @return void
	 */
	public static function setUpTestEnvironment($dir = 'multikernel_convert')
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set the fixtures directory for the tests
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/' . $dir;
		
		// Adjust the autoloader psr-4 fallback dirs
		$loader = current(current(spl_autoload_functions()));
		/** @var ClassLoader $loader */
		self::writeAttribute($loader, 'fallbackDirsPsr4', array_merge(self::readAttribute($loader, 'fallbackDirsPsr4'), [
			self::$fixturesDir . '/src',
		]));
	}
	
	/**
	 * Internal function to set up the test environment for functional tests.
	 *
	 * @return void
	 */
	private function setUpFunctionalTestEnvironment()
	{
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment();
		
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Check the project directory
		self::callMethod(self::$command, 'checkProjectDirectory', self::$fixturesDir);
		
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment('multikernel_convert_functional');
		
		// Change to the fixtures directory
		chdir(self::$fixturesDir);
	}
	
	/**
	 * Returns the fixture directory for the specified type and app.
	 *
	 * @param string $app App subdirectory name (if not loading a BootKernel)
	 * @return string
	 */
	protected static function getFixturesDir($app = null)
	{
		// Return a different path for the standard multikernel test app
		if ('app' === $app) {
			return self::$fixturesDir . '/apps' . ($app ? '/' . $app : '');
		}
		
		// Return the path to the app dir for the specified environment
		return self::$fixturesDir . ($app ? '/' . $app : '');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'dev', $debug = false)
	{
		// Create a buffered output for the tests
		self::$output = new BufferedOutput();
		
		// Set up the application and command instances if an app has been specified
		if (null !== $app)
		{
			// Dont call the parent method
			ApplicationTestCase::setUp($app, $environment, $debug);
			
			// Get the command instance to test
			self::$command = self::$application->get($this->commandName);
			$this->writeAttribute(self::$command, 'output', self::$output);
		}
		
		// Override the output of Generator and FilesystemManipulator
		$this->writeAttribute(Generator::class, 'output', self::$output);
		$this->writeAttribute(FilesystemManipulator::class, 'output', self::$output);
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown()
	{
		// Fixtures dir property is non-empty
		if (null !== self::$fixturesDir)
		{
			// Make the fixtures directory read-write again
			self::getFs()->chmod(self::$fixturesDir, 0755);
			
			// Rename the app kernel back to its original name
			$dir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/multikernel_convert/multikernel_convert_app';
			if (is_file($dir . '/MultikernelConvertAppKernel.txt')) {
				self::getFs()->rename($dir . '/MultikernelConvertAppKernel.txt', $dir . '/MultikernelConvertAppKernel.php');
			}
		}
	}
	
	/**
	 * @covers ::isEnabled()
	 * @kernelEnvironment dev
	 * @testdox isEnabled() returns TRUE in environment 'dev'
	 */
	public function test_isEnabled_in_dev_environment()
	{
		// Set up the dev environment
		$this->setUp('multikernel_convert_app', 'dev');
		
		// Check that isEnabled() returns true
		$this->assertTrue(self::$command->isEnabled());
	}

	/**
	 * @covers ::isEnabled()
	 * @kernelEnvironment test
	 * @testdox isEnabled() returns TRUE in environment 'test'
	 */
	public function test_isEnabled_in_test_environment()
	{
		// Set up the dev environment
		$this->setUp('multikernel_convert_app', 'test');
		
		// Check that isEnabled() returns true
		$this->assertTrue(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "multikernel:convert" does not exist.
	 * @kernelEnvironment prod
	 * @testdox isEnabled() returns FALSE in environment 'prod'
	 */
	public function test_isEnabled_in_prod_environment()
	{
		// Set up the dev environment
		$this->setUp('multikernel_convert_app', 'prod');
	}
	
	/**
	 * @covers ::configure()
	 * @testdox configure() sets up the command correctly
	 */
	public function test_configure()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Check the command name has been initialized correctly
		$this->assertEquals('multikernel:convert', self::$command->getName());
		
		// Check the command description has been initialized correctly
		$this->assertEquals('Converts a project to a multikernel project', self::$command->getDescription());
		
		// Check the command help has been initialized correctly
		$this->assertEquals(<<<EOH
The <info>multikernel:convert</info> command changes the filesystem
structure of a Symfony Standard Edition project to a multikernel project.

The command is only available on a regular app kernel and is disabled
after conversion.

To convert your project to a multikernel project, run:

  <info>php %command.full_name%</info>

After converting the project filesystem structure, run:

  <info>composer dump-autoload</info>
  <info>composer symfony-scripts</info>

EOH
		, self::$command->getHelp());
		
		// Check the input definition of the command has no arguments and options
		$this->assertEmpty(self::$command->getDefinition()->getArguments());
		$this->assertEmpty(self::$command->getDefinition()->getOptions());
	}
	
	/**
	 * @covers ::createGenerator()
	 * @testdox createGenerator() returns a BootKernelGenerator instance
	 */
	public function test_createGenerator()
	{
		// Get the generator
		$generator = $this->callMethod(self::$command, 'createGenerator');
		
		// Check the generator is an instance of the corect class
		$this->assertInstanceOf(BootKernelGenerator::class, $generator);
	}
	
	/**
	 * @covers ::getSkeletonDirs()
	 * @testdox getSkeletonDirs() returns the correct template paths
	 */
	public function test_getSkeletonDirs()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Get the path of the SensioGeneratorBundle
		$bundle = self::$application->getKernel()->getBundle('SensioGeneratorBundle');
		$sensioBundlePath = $bundle->getPath();
		
		// Get the path of the MotanaMultikernelBundle
		$bundle = self::$application->getKernel()->getBundle('MotanaMultikernelBundle');
		$motanaBundlePath = $bundle->getPath();
		
		// Get the skeleton dirs
		$dirs = $this->callMethod(self::$command, 'getSkeletonDirs', self::$kernel->getBundle('MotanaMultikernelBundle'));
		
		// Check the returned skeleton dirs are correct
		$this->assertEquals([
			$sensioBundlePath . '/Command/../Resources/skeleton',
			$sensioBundlePath . '/Command/../Resources',
			$motanaBundlePath . '/Command/../Resources/skeleton',
			$motanaBundlePath . '/Command/../Resources',
		], $dirs);
	}
	
	/**
	 * @covers ::getSkeletonDirs()
	 * @testdox getSkeletonDirs() returns the correct template paths for a bundle
	 */
	public function test_getSkeletonDirs_with_bundle()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Get the path of the SensioGeneratorBundle
		$bundle = self::$application->getKernel()->getBundle('SensioGeneratorBundle');
		$sensioBundlePath = $bundle->getPath();
		
		// Get the path of the MotanaMultikernelBundle
		$bundle = self::$application->getKernel()->getBundle('MotanaMultikernelBundle');
		$motanaBundlePath = $bundle->getPath();
		
		// Get the GenerateAppBundle
		$bundle = new \MultikernelConvertAppBundle\MultikernelConvertAppBundle();
		$bundlePath = $bundle->getPath();
		$kernelPath = self::$application->getKernel()->getRootDir();
		
		// Create dummy skeleton directories for the test
		self::getFs()->mkdir($bundlePath . '/Resources/MotanaMultikernelBundle/skeleton');
		self::getFs()->mkdir($kernelPath . '/Resources/MotanaMultikernelBundle/skeleton');

		// Get the skeleton dirs
		$dirs = $this->callMethod(self::$command, 'getSkeletonDirs', $bundle);
		
		// Remove the dummy skeleton directories
		self::getFs()->remove($bundlePath . '/Resources/MotanaMultikernelBundle');
		self::getFs()->remove($kernelPath . '/Resources/MotanaMultikernelBundle');
		
		// Check the returned skeleton dirs are correct
		$this->assertEquals([
			$sensioBundlePath . '/Command/../Resources/skeleton',
			$sensioBundlePath . '/Command/../Resources',
			$bundlePath . '/Resources/MotanaMultikernelBundle/skeleton',
			$kernelPath . '/Resources/MotanaMultikernelBundle/skeleton',
			$motanaBundlePath . '/Command/../Resources/skeleton',
			$motanaBundlePath . '/Command/../Resources',
		], $dirs);
	}
	
	/**
	 * @covers ::checkProjectDirectory()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessageRegExp |^No composer\.json found in the "(.*)" directory\.$|
	 * @testdox checkProjectDirectory() checks the target directory contains a composer.json
	 */
	public function test_checkProjectDirectory_checks_composer_json()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Rename the composer.json for the test
		self::getFs()->rename(self::$fixturesDir . '/composer.json', self::$fixturesDir . '/composer.nojs');
		
		// Check that an exception is thrown
		try {
			$this->callMethod(self::$command, 'checkProjectDirectory', self::$fixturesDir);
		}
		
		// Rename the composer.json back to its original name
		finally {
			self::getFs()->rename(self::$fixturesDir . '/composer.nojs', self::$fixturesDir . '/composer.json');
		}
	}
	
	/**
	 * @covers ::checkProjectDirectory()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessageRegExp |^Not enough permissions to write to the "(.*)" directory\.$|
	 * @testdox checkProjectDirectory() checks the target directory is writable
	 */
	public function test_checkProjectDirectory_checks_permissions()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Make the fixtures directory read-only
		self::getFs()->chmod(self::$fixturesDir, 0555);
		
		// Check that an exception is thrown
		try {
			$this->callMethod(self::$command, 'checkProjectDirectory', self::$fixturesDir);
		}
		
		// Make the fixtures directory read-write again
		finally {
			self::getFs()->chmod(self::$fixturesDir, 0755);
		}
	}
	
	/**
	 * @covers ::checkProjectDirectory()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessageRegExp |^No app kernels found in the "(.*)" directory\.$|
	 * @testdox checkProjectDirectory() checks the target directory is writable
	 */
	public function test_checkProjectDirectory_checks_kernels()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Get the fixtures directory
		$dir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/multikernel_convert';
		
		// Rename the app kernel to make the command unable to find it
		self::getFs()->rename($dir . '/multikernel_convert_app/MultikernelConvertAppKernel.php', $dir . '/multikernel_convert_app/MultikernelConvertAppKernel.txt');
		
		// Check that an exception is thrown
		try {
			$this->callMethod(self::$command, 'checkProjectDirectory', self::$fixturesDir);
		}
		
		// Rename the app kernel back to its original name
		finally {
			self::getFs()->rename($dir . '/multikernel_convert_app/MultikernelConvertAppKernel.txt', $dir . '/multikernel_convert_app/MultikernelConvertAppKernel.php');
		}
	}
	
	/**
	 * @covers ::checkProjectDirectory()
	 * @testdox checkProjectDirectory() sets up properties correctly
	 */
	public function test_checkProjectDirectory()
	{
		// Set up the test environment
		$this->setUp('multikernel_convert_app');
		
		// Call the method
		$this->callMethod(self::$command, 'checkProjectDirectory', self::$fixturesDir);
		
		// Check that the kernelClassFiles property contains the correct content
		$this->assertEquals([
			self::$fixturesDir . '/multikernel_convert_app/MultikernelConvertAppKernel.php' => 'multikernel_convert_app/MultikernelConvertAppKernel.php',
		], array_map(function(SplFileInfo $file){
			return $file->getRelativePathname();
		}, $this->readAttribute(self::$command, 'kernelClassFiles')));
		
		// Check that the cacheClassFiles property contains the correct content
		$this->assertEquals([
			self::$fixturesDir . '/multikernel_convert_app/MultikernelConvertAppCache.php' => 'multikernel_convert_app/MultikernelConvertAppCache.php',
		], array_map(function(SplFileInfo $file){
			return $file->getRelativePathname();
		}, $this->readAttribute(self::$command, 'cacheClassFiles')));
	}
	
	/**
	 * @covers ::createBootKernelSkeleton()
	 * @testdox createBootKernelSkeleton() returns correct output and generates files
	 */
	public function test_createBootKernelSkeleton()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Override the output property of the command instance
		$this->writeAttribute(self::$command, 'output', self::$output);
		
		// Call the method
		$this->callMethod(self::$command, 'createBootKernelSkeleton', self::$fixturesDir);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('create_boot_kernel', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check files have been generated
		BootKernelGeneratorTest::assertBootKernelExists(self::$fixturesDir);
	}
	
	/**
	 * @covers ::copyKernelDirectory()
	 * @testdox copyKernelDirectory() returns correct output and copies files
	 */
	public function test_copyKernelDirectory()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Override the output property of the command instance
		$this->writeAttribute(self::$command, 'output', self::$output);
		
		// Determine the source and target directories
		$src = self::$fixturesDir . '/multikernel_convert_functional_app';
		$target = self::$fixturesDir . '/apps/multikernel_convert_functional_app';
		
		// Call the method
		$this->callMethod(self::$command, 'copyKernelDirectory', $src, $target);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('copy_kernel_directory', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check the copied app exists in the target directory
		$this->assertAppExists($target);
	}
	
	/**
	 * @covers ::updateKernelClass()
	 * @testdox updateKernelClass() changes a kernel class as expected
	 */
	public function test_updateKernelClass()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Determine the target filename
		$target = self::$fixturesDir . '/apps/multikernel_convert_functional_app/MultikernelConvertFunctionalAppKernel.php';
		
		// Call the method
		$this->callMethod(self::$command, 'updateKernelClass', $target);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('update_kernel_class', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check the kernel class contains the correct modifications
		$this->assertKernelClassModified($target);
	}
	
	/**
	 * @covers ::updateKernelConfiguration()
	 * @testdox updateKernelConfiguration() changes configuration of a kernel as expected
	 */
	public function test_updateKernelConfiguration()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Determine the target directory
		$target = self::$fixturesDir . '/apps/multikernel_convert_functional_app/config';
		
		// Call the method
		$this->callMethod(self::$command, 'updateKernelConfiguration', $target);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('update_kernel_configuration', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check the correct modifications were made
		$this->assertKernelConfigurationModified($target);
	}

	/**
	 * @covers ::copyKernel()
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @testdox copyKernel() returns correct output and makes the correct changes
	 */
	public function test_copyKernel()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Determine the source and target directories
		$dir = self::$fixturesDir . '/multikernel_convert_functional_app';
		$kernel = $dir . '/MultikernelConvertFunctionalAppKernel.php';
		$relativePathname = basename($dir) . '/' . basename($kernel);
		$relativePath = dirname($relativePathname);
		
		// Call the method
		$this->callMethod(self::$command, 'copyKernel', self::$fixturesDir, new SplFileInfo($kernel, $relativePath, $relativePathname));
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('copy_kernel', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Determine the target directory to check
		$target = self::$fixturesDir . '/apps/multikernel_convert_functional_app';
		
		// Check the copied app exists in the target directory
		$this->assertAppExists($target);
		
		// Check the kernel class contains the correct modifications
		$this->assertKernelClassModified($target . '/MultikernelConvertFunctionalAppKernel.php');
		
		// Check the kernel configuration contains the correct modifications
		$this->assertKernelConfigurationModified($target . '/config');
	}
	
	/**
	 * @covers ::updateComposerJson()
	 * @testdox updateComposerJson() makes the correct changes
	 */
	public function test_updateComposerJson()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();

		// Save the previous content of composer.json
		self::$composerJson = file_get_contents(self::$fixturesDir . '/composer.json');
		
		// Call the method
		$this->callMethod(self::$command, 'updateComposerJson', self::$fixturesDir);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('update_composer_json', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check composer.json contains the correct modifications
		$this->assertComposerConfigurationModified(self::$fixturesDir . '/composer.json');

		// Check the correct messages were generated
		$this->assertEquals([
			'The <info>motana/multikernel</info> package has been added to the require section of composer.json.',
			'You should run <comment>composer update</comment> to update your dependencies.',
		], $this->readAttribute(self::$command, 'messages'));
	}

	/**
	 * @covers ::updateComposerJson()
	 * @testdox updateComposerJson() makes the correct changes (without requirement changes)
	 */
	public function test_updateComposerJson_without_changed_requirements()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Restore the composer.json from before running test_updateComposerJson()
		// but add the motana/multikernel dependency
		$config = json_decode(self::$composerJson, true);
		$config['require']['motana/multikernel'] = '~1.3';
		self::getFs()->dumpFile(self::$fixturesDir . '/composer.json', json_encode($config, JSON_PRETTY_PRINT));
		
		// Call the method
		$this->callMethod(self::$command, 'updateComposerJson', self::$fixturesDir);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('update_composer_json_no_req_changes', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check composer.json contains the correct modifications
		$this->assertComposerConfigurationModified(self::$fixturesDir . '/composer.json');
		
		// Check the correct messages were generated
		$this->assertEquals([
			'The autoloader classmap in composer.json has been updated.',
			'You should run <comment>composer dump-autoload</comment>',
		], $this->readAttribute(self::$command, 'messages'));
	}
	
	/**
	 * @covers ::cleanupProjectDirectory()
	 * @testdox cleanupProjectDirectory removes old app directories, cache, logs and sessions
	 */
	public function test_cleanupProjectDirectory()
	{
		// Set up the environment for functional tests
		$this->setUpFunctionalTestEnvironment();
		
		// Re-check the project directory
		self::callMethod(self::$command, 'checkProjectDirectory', self::$fixturesDir);
		
		// Change to the fixtures directory
		chdir(self::$fixturesDir);
		
		// Create some dummy files to remove
		self::getFs()->dumpFile(self::$fixturesDir . '/var/cache/multikernel_convert_functional_app/classes.map', '');
		self::getFs()->dumpFile(self::$fixturesDir . '/var/logs/multikernel_convert_functional_app/dev.log', '');
		self::getFs()->dumpFile(self::$fixturesDir . '/var/sessions/multikernel_convert_functional_app/sess_' . md5('test'), '');

		// Call the method
		self::callMethod(self::$command, 'cleanupProjectDirectory', self::$fixturesDir);
		
		// Check the correct output was returned
		$this->assertEquals($this->getTemplate('cleanup_project_directory', [], 'txt', $this->commandName), self::$output->fetch());
		
		// Check the old app directory is removed
		$this->assertDirectoryNotExists(self::$fixturesDir . '/multikernel_convert_functional_app');
		
		// Check the cache, logs and sessions directories in var/ are empty
		foreach([ 'cache', 'logs', 'sessions' ] as $subdir) {
			if (is_dir($path = self::$fixturesDir . '/var/' . $subdir)) {
				$files = iterator_to_array(Finder::create()->files()->notName('.gitkeep')->in($path));
				$this->assertEmpty($files);
			}
		}
	}
	
	/**
	 * @covers ::execute()
	 * @testdox execute() checks the target directory contains a composer.json
	 */
	public function test_execute_checks_composer_json()
	{
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment();
		
		// Rename the composer.json for the test
		self::getFs()->rename(self::$fixturesDir . '/composer.json', self::$fixturesDir . '/composer.nojs');

		// Check that execute() shows an error message when no composer.json is there
		$this->test_execute('multikernel_convert_app', 'execute_checks_composer_json');
		
		// Rename the composer.json back to its original name
		self::getFs()->rename(self::$fixturesDir . '/composer.nojs', self::$fixturesDir . '/composer.json');
	}
	
	/**
	 * @covers ::execute()
	 * @testdox execute() checks the target directory is writable
	 */
	public function test_execute_checks_permissions()
	{
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment();
		
		// Make the fixtures directory read-only
		self::getFs()->chmod(self::$fixturesDir, 0555);
		
		// Check that execute() shows an error message when the directory is not writable
		$this->test_execute('multikernel_convert_app', 'execute_checks_permissions');

		// Make the fixtures directory read-write again
		self::getFs()->chmod(self::$fixturesDir, 0755);
	}
	
	/**
	 * @covers ::execute()
	 * @kernelDir default
	 * @testdox execute() checks the target directory contains app kernels
	 */
	public function test_execute_checks_kernels()
	{
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment();
		
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment('default');
		
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the test environment
		$this->setUp('app');
		
		// Get the current working directory
		$cwd = getcwd();
		
		// Change to the fixtures directory
		chdir($dir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/multikernel_convert');

		// Rename the app kernel to make the command unable to find it
		self::getFs()->rename($dir . '/multikernel_convert_app/MultikernelConvertAppKernel.php', $dir . '/multikernel_convert_app/MultikernelConvertAppKernel.txt');
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => $this->commandName ], $this->commandParameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		$this->callMethod(self::$command, 'execute', $input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate('execute_checks_kernels', $options, 'txt', $this->commandName), self::$output->fetch());

		// Rename the app kernel back to its original name
		self::getFs()->rename($dir . '/multikernel_convert_app/MultikernelConvertAppKernel.txt', $dir . '/multikernel_convert_app/MultikernelConvertAppKernel.php');
		
		// Remove the files generated for the test
		chdir($cwd);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::provide_testExecute_data()
	 */
	public function provide_test_execute_data() {
		return [
			'converts a project' => [
				'multikernel_convert_app',
				'execute_convert_project',
				[]
			],
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() returns correct output and
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();
		
		// Set up psr-4 autoload paths
		self::setUpTestEnvironment();
		
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the test environment
		$this->setUp($app);

		// Get the current working directory
		$cwd = getcwd();
		
		// Change to the fixtures directory
		chdir(self::$fixturesDir);
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => $this->commandName ], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		$this->callMethod(self::$command, 'execute', $input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, 'txt', $this->commandName), self::$output->fetch());
		
		// Remove the files generated for the test
		chdir($cwd);
		
		// Not testing an error condition
		if (0 !== strpos($template, 'execute_checks'))
		{
			// Check the boot kernel skeleton has been generated
			BootKernelGeneratorTest::assertBootKernelExists(self::$fixturesDir);
			
			// Check the copied app exists in the target directory
			$this->assertAppExists(self::$fixturesDir . '/apps/multikernel_convert_app');
			
			// Check the kernel class contains the correct modifications
			$this->assertKernelClassModified(self::$fixturesDir . '/apps/multikernel_convert_app/MultikernelConvertAppKernel.php');
			
			// Check the kernel configuration contains the correct modifications
			$this->assertKernelConfigurationModified(self::$fixturesDir . '/apps/multikernel_convert_app/config');
			
			// Check composer.json contains the correct modifications
			$this->assertComposerConfigurationModified(self::$fixturesDir . '/composer.json');
			
			// Check the old app directory has been removed
			$this->assertDirectoryNotExists(self::$fixturesDir . '/multikernel_convert_app');
			
			// Check the cache, logs and sessions directories in var/ are empty
			foreach([ 'cache', 'logs', 'sessions' ] as $subdir) {
				if (is_dir($path = self::$fixturesDir . '/var/' . $subdir)) {
					$files = iterator_to_array(Finder::create()->files()->notName('.gitkeep')->in($path));
					$this->assertEmpty($files);
				}
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::provide_test_run_data()
	 */
	public function provide_test_run_data()
	{
		return [
			'converts a project' => [
				'multikernel_convert_run_app',
				'run_convert_project',
				[]
			],
		];
	}

	/**
	 * @covers ::run()
	 * @covers ::execute()
	 * @dataProvider provide_test_run_data
	 * @kernelDir multikernel_convert_run
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox run() returns correct output and
	 */
	public function test_run($app, $template, array $parameters = [])
	{
		// Restore previous psr-4 autoload paths
		self::tearDownTestEnvironment();

		// Set up psr-4 autoload paths
		self::setUpTestEnvironment('multikernel_convert_run');
		
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the test environment
		$this->setUp($app);
		
		// Get the current working directory
		$cwd = getcwd();
		
		// Change to the fixtures directory
		chdir(self::$fixturesDir);
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => $this->commandName ], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		self::$command->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, 'txt', $this->commandName), self::$output->fetch());
		
		// Remove the files generated for the test
		chdir($cwd);
		
		// Check the old app directory has been removed
		$this->assertDirectoryNotExists(self::$fixturesDir . '/multikernel_convert_run_app');
		
		// Check the boot kernel skeleton has been generated
		BootKernelGeneratorTest::assertBootKernelExists(self::$fixturesDir);
		
		// Check the copied app exists in the target directory
		$this->assertAppExists(self::$fixturesDir . '/apps/multikernel_convert_run_app');
		
		// Check the kernel class contains the correct modifications
		$this->assertKernelClassModified(self::$fixturesDir . '/apps/multikernel_convert_run_app/MultikernelConvertRunAppKernel.php');
		
		// Check the kernel configuration contains the correct modifications
		$this->assertKernelConfigurationModified(self::$fixturesDir . '/apps/multikernel_convert_run_app/config');
		
		// Check composer.json contains the correct modifications
		$this->assertComposerConfigurationModified(self::$fixturesDir . '/composer.json');
		
		// Check the old app directory has been removed
		$this->assertDirectoryNotExists(self::$fixturesDir . '/multikernel_convert_run_app');
		
		// Check the cache, logs and sessions directories in var/ are empty
		foreach([ 'cache', 'logs', 'sessions' ] as $subdir) {
			if (is_dir($path = self::$fixturesDir . '/var/' . $subdir)) {
				$files = iterator_to_array(Finder::create()->files()->notName('.gitkeep')->in($path));
				$this->assertEmpty($files);
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::convertParametersToOptions()
	 */
	protected static function convertParametersToOptions(array $parameters = [])
	{
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::filterCommandParameters()
	 */
	protected static function filterCommandParameters(array $parameters = [])
	{
		unset($parameters['command']);
		unset($parameters['--format']);
		
		return $parameters;
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
	 * Assert that a copied app directory exists.
	 *
	 * @param string $dir Target directory the app has been copied to
	 * @return void
	 */
	public static function assertAppExists($dir)
	{
		// Get the camelized version of the kernel directory name
		$kernelName = BootKernel::camelizeKernelName(basename($dir));
		
		// Check the autoload.php has been removed
		self::assertFileNotExists($dir . '/autoload.php');
		
		// Check the .htaccess exists
		self::assertFileExists($dir . '/.htaccess');
		
		// Check the kernel and cache classes exist
		self::assertFileExists($dir . '/' . $kernelName . 'Kernel.php');
		self::assertFileExists($dir . '/' . $kernelName . 'Cache.php');
		
		// Check the configuration exists
		self::assertFileExists($dir . '/config/config.yml');
		self::assertFileExists($dir . '/config/config_dev.yml');
		self::assertFileExists($dir . '/config/config_prod.yml');
		self::assertFileExists($dir . '/config/config_test.yml');
		self::assertFileExists($dir . '/config/parameters.yml');
		self::assertFileExists($dir . '/config/parameters.yml.dist');
		self::assertFileExists($dir . '/config/routing.yml');
		self::assertFileExists($dir . '/config/routing_dev.yml');
		self::assertFileExists($dir . '/config/security.yml');
		self::assertFileExists($dir . '/config/services.yml');
		
		// Check the twig templates exist
		self::assertFileExists($dir . '/Resources/views/base.html.twig');
		self::assertFileExists($dir . '/Resources/views/default/index.html.twig');
	}
	
	/**
	 * Assert that a kernel include file contains the correct modifications.
	 *
	 * @param string $file Kernel include filename
	 * @return void
	 */
	public static function assertKernelClassModified($file)
	{
		// Methods that should be removed
		$methods = [
			'getCacheDir',
			'getLogDir',
			'registerContainerConfiguration',
		];
		
		// Use clauses that should be replaced (or removed, when there is a NULL replacement)
		$uses = [
			'Symfony\\Component\\HttpKernel\\Kernel' => 'Motana\\Bundle\\MultikernelBundle\\HttpKernel\\Kernel',
			'Symfony\\Component\\Config\\Loader\\LoaderInterface' => null,
		];
		
		// Read the target file
		$content = file_get_contents($file);
		
		// Check the correct methods are removed
		foreach ($methods as $method) {
			self::assertNotContains('public function ' . $method . '(', $content);
		}
		
		// Check the use clause modifications are correct
		foreach ($uses as $oldClassOrNamespace => $newClassOrNamespace) {
			self::assertNotContains('use ' . $oldClassOrNamespace . ';', $content);
			if (null !== $newClassOrNamespace) {
				self::assertContains('use ' . $newClassOrNamespace . ';', $content);
			}
		}
	}
	
	/**
	 * Assert that a kernel configuration directory contains the correct modifications.
	 *
	 * @param string $dir Configuration directory
	 * @return void
	 */
	public static function assertKernelConfigurationModified($dir)
	{
		// Files to check
		$files = [
			'config.yml',
			'config_dev.yml',
			'services.yml',
		];
		
		// Process all files
		foreach ($files as $file)
		{
			// Skip non-existing files
			if ( ! is_file($dir . '/' . $file)) {
				continue;
			}
			
			// Detect the environment from the filename
			$environment = null;
			$basename = basename($file, '.yml');
			if (false !== strpos($basename, '_')) {
				$parts = explode('_', $basename);
				$basename = reset($parts);
				$environment = end($parts);
			}
			
			// Read the configuration file
			$config = Yaml::parse(file_get_contents($dir . '/' . $file));
			
			// Check the file contains the correct modifications
			switch ($basename)
			{
				case 'config':
					// Get the expected name of the routing configuration file
					$routingFile = 'routing' . ($environment ? '_' . $environment : '') . '.yml';
					
					// Check the router resource path modification is correct
					if (isset($config['framework']['router']['resource'])) {
						self::assertEquals('%kernel.project_dir%/apps/%kernel.name%/config/' . $routingFile, $config['framework']['router']['resource']);
					}
					
					// Check the session save path modification is correct
					if (isset($config['framework']['session']['save_path'])) {
						self::assertEquals('%kernel.project_dir%/var/sessions/%kernel.name%/%kernel.environment%', $config['framework']['session']['save_path']);
					}
					break;
					
				case 'services':
					// Check the services configuration modification is correct
					if (isset($config['services'])) {
						foreach ($config['services'] as $classOrNamespace => $section) {
							if (isset($section['resource']) && false !== strpos($section['resource'], '/src')) {
								self::assertContains('%kernel.project_dir%/src', $section['resource']);
							}
							if (isset($section['exclude']) && false !== strpos($section['exclude'], '/src')) {
								self::assertContains('%kernel.project_dir%/src', $section['exclude']);
							}
						}
					}
					break;
			}
		}
	}
	
	/**
	 * Assert that a composer.json contains the correct modifications.
	 *
	 * @param string $file Path to composer.json
	 * @return void
	 */
	public static function assertComposerConfigurationModified($file)
	{
		// Read composer.json
		$config = json_decode(file_get_contents($file), true);
		
		// Check the configuration contains incenteev-parameters
		self::assertTrue(isset($config['extra']['incenteev-parameters']));
		
		// Convert flat array to multidimensional array if required
		if (isset($config['extra']['incenteev-parameters']['file'])) {
			$config['extra']['incenteev-parameters'] = [ $config['extra']['incenteev-parameters'] ];
		}
		
		// Add all files listed in incenteev-parameters to the list
		$parameterFiles = [];
		foreach ($config['extra']['incenteev-parameters'] as $index => $record) {
			$parameterFiles[] = $record['file'];
		}
		
		// Remove parameter filenames not in the apps/ subdirectory
		$filtered = array_filter($parameterFiles, function($file) {
			return 0 === strpos($file, 'apps/');
		});
		
		// Check the filtered array equals the original
		self::assertEquals($parameterFiles, $filtered);
		
		// Check the requirement for the motana/multikernel package has been added
		self::assertArrayHasKey('motana/multikernel', $config['require']);
		
		// Check the requirement for the motana/multikernel package has the correct version
		self::assertEquals('~1.3', $config['require']['motana/multikernel']);
		
		// Check the autoloader class map contains the boot kernel
		self::assertContains('apps/BootKernel.php', $config['autoload']['classmap']);
	}
}
