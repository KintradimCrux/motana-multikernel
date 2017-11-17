<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Generator;

use Motana\Bundle\MultikernelBundle\Generator\BootKernelGenerator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Generator\BootKernelGenerator
 * @testdox Motana\Bundle\MultikernelBundle\Generator\BootKernelGenerator
 */
class BootKernelGeneratorTest extends TestCase
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
	 * @var BootKernelGenerator
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/generator/bootkernel';

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
		// Create the fixtures directory
		self::getFs()->mkdir(self::$fixturesDir);
		
		// Create the generator instance
		self::$generator = new BootKernelGenerator();
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
	 * Assert that a boot kernel environment exists in the specified directory.
	 *
	 * @param string $dir Directory name
	 */
	public static function assertBootKernelExists($dir)
	{
		// Check that bin/console was created
		self::assertFileExists(sprintf('%s/bin/console', $dir));
		
		// Check the front controller files were created
		self::assertFileExists(sprintf('%s/web/app.php', $dir));
		self::assertFileExists(sprintf('%s/web/app_dev.php', $dir));
		
		// Check the boot kernel files were created
		self::assertFileExists(sprintf('%s/apps/.htaccess', $dir));
		self::assertFileExists(sprintf('%s/apps/autoload.php', $dir));
		self::assertFileExists(sprintf('%s/apps/BootKernel.php', $dir));
		
		// Check the boot kernel configuration files were created
		self::assertDirectoryExists(sprintf('%s/apps/config', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config_dev.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config_prod.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/config_test.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/parameters.yml', $dir));
		self::assertFileExists(sprintf('%s/apps/config/parameters.yml.dist', $dir));
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
	 * @covers ::generateBootKernel()
	 * @testdox generateBootKernel() generates a boot kernel
	 */
	public function test_generateBootKernel()
	{
		// Change to the fixtures directory
		chdir(self::$fixturesDir);
		
		// Generate the boot kernel environment in the temporary directory
		self::$generator->generateBootkernel([ 'app' ]);
		
		// Check the boot kernel environment has been generated
		$this->assertBootKernelExists(self::$fixturesDir);
	}
}
