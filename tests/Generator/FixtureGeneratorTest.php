<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Generator;

use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator
 * @testdox Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator
 */
class FixtureGeneratorTest extends TestCase
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
	 * @var FixtureGenerator
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/generator/fixture';
		
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
		self::$generator = new FixtureGenerator();
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
	 * @covers ::__construct()
	 * @testdox __construct() initializes skeleton directories correctly
	 */
	public function test_constructor()
	{
		// Get the filesystem path of the generator class
		$class = new \ReflectionClass(self::$generator);
		$dir = dirname($class->getFileName());
		
		// Check the skeleton dirs have been initialized correctly
		$this->assertEquals([
			$dir . '/../Resources/fixtures',
			$dir . '/../Resources/skeleton',
		], $this->readAttribute(self::$generator, 'skeletonDirs'));
	}
	
	/**
	 * @covers ::generateRandomSecret()
	 * @testdox generateRandomSecret() returns a SHA1 hash
	 */
	public function test_generateRandomSecret()
	{
		// Generate a random secret
		$hash = self::$generator->generateRandomSecret();
		
		// Check the random secret is a SHA1 hash
		$this->assertRegExp('|^[0-9a-z]{40}$|', $hash);
	}
	
	/**
	 * @covers ::generateConfig()
	 * @testdox generateConfig() generates configuration files from templates
	 */
	public function test_generateConfig()
	{
		// Generate an app config file
		self::$generator->generateConfig('app/config.yml.twig', self::$fixturesDir . '/config.yml', [
			'kernel_name' => 'app',
			'bundles' => [
				'MotanaMultikernelBundle' => false,
				'DoctrineBundle' => false,
				'SwiftmailerBundle' => false,
			]
		]);
		
		// Check the file has been generated
		$this->assertFileExists(self::$fixturesDir . '/config.yml');
		
		// Get the generated file content
		$content = file_get_contents(self::$fixturesDir . '/config.yml');
		
		// Check the file contains the expected content
		$this->assertEquals($this->generateConfigFileContent(), $content);
	}
	
	/**
	 * @covers ::generateCommandOutput()
	 * @testdox generateCommandOutput() generates command output from templates
	 */
	public function test_generateCommandOutput()
	{
		// Template parameters
		$kernelDebug = 'false';
		$kernelEnvironment = 'test';
		$kernelName = 'boot';
		
		// Generate output for the list command
		$output = self::$generator->generateCommandOutput('command_multikernel_namespace', 'txt', [
			'command_name' => 'list',
			'kernel_name' => $kernelName,
		]);
		
		// Check the generated output is correct
		$this->assertEquals($this->generateListCommandOutput($kernelName, $kernelEnvironment, $kernelDebug), $output);
	}
	
	/**
	 * @covers ::generateCommandOutput()
	 * @testdox generateCommandOutput() returns an empty string for unknown template names
	 */
	public function test_generateCommandOutput_with_invalid_template_name()
	{
		$this->assertEquals('', self::$generator->generateCommandOutput('not_existing_template', 'txt', [
			'command_name' => 'list',
		]));
	}
	
	/**
	 * @covers ::generateDescriptorOutput()
	 * @testdox generateDescriptorOutput() generates descriptor output from templates
	 */
	public function test_generateDescriptorOutput()
	{
		// Template parameters
		$kernelDebug = 'false';
		$kernelEnvironment = 'test';
		$kernelName = 'boot';
		
		// Generate output for the list command
		$output = self::$generator->generateDescriptorOutput('application_multikernel_namespace', 'txt', [
			'kernel_name' => $kernelName,
		]);
		
		// Check the generated output is correct
		$this->assertEquals($this->generateListCommandOutput($kernelName, $kernelEnvironment, $kernelDebug), $output);
	}
	
	/**
	 * @covers ::generateDescriptorOutput()
	 * @testdox generateDescriptorOutput() returns an empty string for unknown template names
	 */
	public function test_generateDescriptorOutput_with_invalid_template_name()
	{
		$this->assertEquals('', self::$generator->generateDescriptorOutput('not_existing_template', 'txt', [
			'command_name' => 'list',
		]));
	}
	
	/**
	 * @covers ::generateEmptyKernelClass()
	 * @testdox generateEmptyKernelClass() generates a minimalist Kernel class from template
	 */
	public function test_generateEmptyKernelClass()
	{
		// Generate an empty kernel class includefile
		self::$generator->generateEmptyKernelClass(self::$fixturesDir . '/EmptyAppKernel.php', 'EmptyAppKernel');
		
		// Check the generated file exists
		$this->assertFileExists(self::$fixturesDir . '/EmptyAppKernel.php');
		
		// Get the file content
		$content = file_get_contents(self::$fixturesDir . '/EmptyAppKernel.php');
		
		// Check the generated file content is correct
		$this->assertEquals($this->generateEmptyAppKernelFileContent('EmptyAppKernel'), $content);
	}
	
	/**
	 * @covers ::generateKernelClass()
	 * @testdox generateKernelClass() generates an AppKernel class from template
	 */
	public function test_generateKernelClass()
	{
		// Generate a cache class file
		self::$generator->generateKernelClass(self::$fixturesDir . '/AppKernel.php', 'AppKernel', [
			'kernel_base_class' => Kernel::class,
			'kernel_base_class_short' => 'Kernel',
			'kernel_class_name' => 'AppKernel',
			'bundle' => false,
			'bundles' => [
				'DoctrineBundle' => false,
				'MotanaMultikernelBundle' => false,
				'SensioDistributionBundle' => false,
				'SensioFrameworkExtraBundle' => false,
				'SwiftmailerBundle' => false,
			],
		]);
		
		// Check the generated file exists
		$this->assertFileExists(self::$fixturesDir . '/AppKernel.php');
		
		// Get the file content
		$content = file_get_contents(self::$fixturesDir . '/AppKernel.php');
		
		// Check the generated file content is correct
		$this->assertEquals($this->generateKernelClassFileContent(), $content);
	}
	
	/**
	 * @covers ::generateCacheClass()
	 * @testdox generateCacheClass() generates an AppCache class from template
	 */
	public function test_generateCacheClass()
	{
		// Generate a cache class file
		self::$generator->generateCacheClass(self::$fixturesDir . '/AppCache.php', 'AppCache');
		
		// Check the generated file exists
		$this->assertFileExists(self::$fixturesDir . '/AppCache.php');
		
		// Get the file content
		$content = file_get_contents(self::$fixturesDir . '/AppCache.php');
		
		// Check the generated file content is correct
		$this->assertEquals($this->generateCacheClassFileContent(), $content);
	}
	
	/**
	 * Generate expected content of a cache class file.
	 *
	 * @return string
	 */
	protected function generateCacheClassFileContent()
	{
		return <<<EOT
<?php

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

class AppCache extends HttpCache
{
}

EOT;
	}
	
	/**
	 * Generate expected content of a config file.
	 *
	 * @return string
	 */
	protected function generateConfigFileContent()
	{
		return <<<EOT
imports:
    - { resource: parameters.yml }
    - { resource: security.yml }
    - { resource: services.yml }

# Put parameters here that don't need to change on each machine where the app is deployed
# https://symfony.com/doc/current/best_practices/configuration.html#application-related-configuration
parameters:
    locale: en

framework:
    #esi: ~
    #translator: { fallbacks: ['%locale%'] }
    secret: '%secret%'
    router:
        resource: '%kernel.project_dir%/app/config/routing.yml'
        strict_requirements: ~
    form: ~
    csrf_protection: ~
    validation: { enable_annotations: true }
    #serializer: { enable_annotations: true }
    templating:
        engines: ['twig']
    default_locale: '%locale%'
    trusted_hosts: ~
    session:
        # https://symfony.com/doc/current/reference/configuration/framework.html#handler-id
        handler_id: session.handler.native_file
        save_path: '%kernel.project_dir%/var/sessions/%kernel.environment%'
    fragments: ~
    http_method_override: true
    assets: ~
    php_errors:
        log: true

# Twig Configuration
twig:
    debug: '%kernel.debug%'
    strict_variables: '%kernel.debug%'



EOT;
	}
	
	/**
	 * Generates expected content of an empty app kernel file.
	 *
	 * @param string $kernelClassName Kernel class name
	 * @return string
	 */
	protected function generateEmptyAppKernelFileContent($kernelClassName)
	{
		return <<<EOT
<?php
use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;
class ${kernelClassName} extends Kernel {
	public function getRootDir() {
		return \$this->rootDir = __DIR__;
	}
	public function registerBundles() {
		return [];
	}
}

EOT;
	}
	
	/**
	 * Generate expected content of a kernel class file.
	 *
	 * @return string
	 */
	protected function generateKernelClassFileContent()
	{
		return <<<EOT
<?php

use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        \$bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
        ];

        if (in_array(\$this->getEnvironment(), [ 'dev', 'test' ], true)) {
            \$bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            \$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();

            if ('dev' === \$this->getEnvironment()) {
                \$bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                \$bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return \$bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.\$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface \$loader)
    {
        \$loader->load(\$this->getRootDir().'/config/config_'.\$this->getEnvironment().'.yml');
    }
}

EOT;
	}
	
	/**
	 * Generates expected output of a list command with namespace debug.
	 *
	 * @param string $kernelName Kernel name
	 * @param string $kernelEnvironment Kernel environment
	 * @param string $kernelDebug Kernel debug flag
	 */
	protected function generateListCommandOutput($kernelName,$kernelEnvironment,$kernelDebug)
	{
		$kernelVersion = Kernel::VERSION;
		return <<<EOT
Motana Multi-Kernel App Console - Symfony ${kernelVersion} (kernel: ${kernelName}, env: ${kernelEnvironment}, debug: ${kernelDebug})

Usage:
  ./bin/console
     To display the list of kernels and commands available on all kernels

  ./bin/console <kernel>
     To display the list of commands available on a kernel

  ./bin/console <command> [options] [--] [arguments]
     To run a command for all kernels supporting it
     Commands available for multiple kernels are marked with *

  ./bin/console <kernel> <command> [options] [--] [arguments]
     To run a command on the on a kernel

Kernels:
  boot
  app

Options:
   -h         --help            Display this help message
   -q         --quiet           Do not output any message
   -V         --version         Display this application version
              --ansi            Force ANSI output
              --no-ansi         Disable ANSI output
   -n         --no-interaction  Do not ask any interactive question
   -e         --env=ENV         The Environment name. [default: "test"]
              --no-debug        Switches off debug mode.
   -v|vv|vvv  --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Commands in namespace "debug":
  *debug:autowiring             Lists classes/interfaces you can use for autowiring
  *debug:config                 Dumps the current configuration for an extension
  *debug:container              Displays current services for an application
  *debug:event-dispatcher       Displays configured listeners for an application
  *debug:router                 Displays current routes for an application
  *debug:twig                   Shows a list of twig functions, filters, globals and tests


EOT;
	}
}