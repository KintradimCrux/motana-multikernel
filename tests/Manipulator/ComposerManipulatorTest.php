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
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator;
use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
 * @testdox Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
 */
class ComposerManipulatorTest extends TestCase
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
	 * @var ComposerManipulator
	 */
	protected static $manipulator;
	
	/**
	 * Output of the manipulator.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/manipulator/composer';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		// Set the output of the Generator classes to a buffered output
		self::$output = new BufferedOutput();
		self::writeAttribute(Generator::class, 'output', self::$output);
		self::writeAttribute(FilesystemManipulator::class, 'output', self::$output);

		// Regenerate composer.json for every test run
		$generator = new FixtureGenerator();
		$generator->generateConfig('config/composer.json.twig', self::$fixturesDir . '/composer.json');
		
		// Create the manipulator to test
		self::$manipulator = new ComposerManipulator(self::$fixturesDir . '/composer.json');
		
		// Remove the composer.lock if it exists
		self::getFs()->remove(self::$fixturesDir . '/composer.lock');
		
		// Fetch output to remove messages
		self::$output->fetch();
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown()
	{
		// Remove the output from the Generator classes
		self::writeAttribute(Generator::class, 'output', null);
		self::writeAttribute(FilesystemManipulator::class, 'output', null);
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Get the content of composer.json
		$json = json_decode(file_get_contents(self::$fixturesDir . '/composer.json'), true);
		
		// Check the properties are initialized correctly
		$this->assertEquals(self::$fixturesDir . '/composer.json', $this->readAttribute(self::$manipulator, 'file'));
		$this->assertEquals($json, $this->readAttribute(self::$manipulator, 'config'));
		$this->assertFalse($this->readAttribute(self::$manipulator, 'requirementsChanged'));
	}
	
	/**
	 * @covers ::hasChangedRequirements()
	 * @testdox hasChangedRequirements() returns requirementsChanged property
	 */
	public function test_hasChangedRequirements()
	{
		// Check there are no changed requirements
		$this->assertFalse(self::$manipulator->hasChangedRequirements());

		// Check that hasChangedRequirements() returns the value of the requirementsChanged property
		$this->writeAttribute(self::$manipulator, 'requirementsChanged', true);
		$this->assertTrue(self::$manipulator->hasChangedRequirements());
	}
	
	/**
	 * @covers ::addRequirements()
	 * @testdox addRequirements() adds packages to require section
	 */
	public function test_addRequirements_add_require()
	{
		// Check that there are changed requirements after adding requirements
		self::$manipulator->addRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertTrue(self::$manipulator->hasChangedRequirements());
		
		// Check the config contains the correct changes
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('foo/bar', $config['require']);
		$this->assertEquals('1.0', $config['require']['foo/bar']);
		
		// Check that addRequirements() does not add the same package version twice
		self::$manipulator->addRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertEquals($config, $this->readAttribute(self::$manipulator, 'config'));
	}
	
	/**
	 * @covers ::addRequirements()
	 * @testdox addRequirements() adds require section with packages
	 */
	public function test_addRequirements_add_require_section()
	{
		// Remove the require section
		$config = $this->readAttribute(self::$manipulator, 'config');
		unset($config['require']);
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that there are changed requirements after adding requirements
		self::$manipulator->addRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertTrue(self::$manipulator->hasChangedRequirements());
		
		// Check the config contains the correct changes
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('foo/bar', $config['require']);
		$this->assertEquals('1.0', $config['require']['foo/bar']);
		
		// Check that addRequirements() does not add the same package version twice
		self::$manipulator->addRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertEquals($config, $this->readAttribute(self::$manipulator, 'config'));
	}
	
	/**
	 * @covers ::addDevRequirements()
	 * @testdox addDevRequirements() adds packages to require-dev section
	 */
	public function test_addDevRequirements_add_require_dev()
	{
		// Check that there are changed requirements after adding dev requirements
		self::$manipulator->addDevRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertTrue(self::$manipulator->hasChangedRequirements());
		
		// Check the config contains the correct changes
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('foo/bar', $config['require-dev']);
		$this->assertEquals('1.0', $config['require-dev']['foo/bar']);
		
		// Check that addDevRequirements() does not add the same package version twice
		self::$manipulator->addDevRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertEquals($config, $this->readAttribute(self::$manipulator, 'config'));
	}
	
	/**
	 * @covers ::addDevRequirements()
	 * @testdox addDevRequirements() adds require-dev section with packages
	 */
	public function test_addDevRequirements_add_require_dev_section()
	{
		// Remove the require section
		$config = $this->readAttribute(self::$manipulator, 'config');
		unset($config['require-dev']);
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that there are changed requirements after adding dev requirements
		self::$manipulator->addDevRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertTrue(self::$manipulator->hasChangedRequirements());
		
		// Check the config contains the correct changes
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('foo/bar', $config['require-dev']);
		$this->assertEquals('1.0', $config['require-dev']['foo/bar']);
		
		// Check that addDevRequirements() does not add the same package version twice
		self::$manipulator->addDevRequirements([ 'foo/bar' => '1.0' ]);
		$this->assertEquals($config, $this->readAttribute(self::$manipulator, 'config'));
	}
	
	/**
	 * @covers ::removeFromClassmap()
	 * @testdox removeFromClassmap() removes files from autoloader classmap
	 */
	public function test_removeFromClassmap()
	{
		// Check the classmap has the correct content
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('classmap', $config['autoload']);
		$this->assertEquals([
			'app/AppKernel.php',
		], $config['autoload']['classmap']);
		
		// Check the classmap is empty after removing the only file from it
		self::$manipulator->removeFromClassmap([ 'app/AppKernel.php' ]);
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertEmpty($config['autoload']['classmap']);
	}
	
	/**
	 * @covers ::addToClassmap()
	 * @testdox addToClassmap() adds files to autoloader classmap
	 */
	public function test_addToClassmap_add_files()
	{
		// Check that addToClassmap() actually adds files to the classmap
		self::$manipulator->addToClassmap([ 'bar/BarKernel.php', 'foo/FooKernel.php' ]);
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('classmap', $config['autoload']);
		$this->assertEquals([
			'app/AppKernel.php',
			'bar/BarKernel.php',
			'foo/FooKernel.php',
		], $config['autoload']['classmap']);
	}
	
	/**
	 * @covers ::addToClassmap()
	 * @testdox addToClassmap() adds autoloader classmap section with files
	 */
	public function test_addToClassmap_add_section()
	{
		$config = $this->readAttribute(self::$manipulator, 'config');
		unset($config['autoload']['classmap']);
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that addToClassmap() actually adds files to the classmap
		self::$manipulator->addToClassmap([ 'bar/BarKernel.php', 'foo/FooKernel.php' ]);
		$config = $this->readAttribute(self::$manipulator, 'config');
		$this->assertArrayHasKey('classmap', $config['autoload']);
		$this->assertEquals([
			'bar/BarKernel.php',
			'foo/FooKernel.php',
		], $config['autoload']['classmap']);
	}
	
	/**
	 * @covers ::getParameterFiles()
	 * @testdox getParameterFiles() returns parameter files
	 */
	public function test_getParameterFiles()
	{
		// Check that incenteev-parameters has the correct content
		$this->assertEquals([
			'app/config/parameters.yml',
			'app/config/foobar.yml',
		], self::$manipulator->getParameterFiles());
	}

	/**
	 * @covers ::getParameterFiles()
	 * @testdox getParameterFiles() returns parameter files from flat array
	 */
	public function test_getParameterFiles_with_flat_array()
	{
		$config = $this->readAttribute(self::$manipulator, 'config');
		$config['extra']['incenteev-parameters'] = [ 'file' => 'app/config/parameters.yml' ];
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that incenteev-parameters has the correct content
		$this->assertEquals([
			'app/config/parameters.yml',
		], self::$manipulator->getParameterFiles());
	}
	
	/**
	 * @covers ::removeParameterFiles()
	 * @testdox removeParameterFiles() removes parameter files
	 */
	public function test_removeParameterFiles()
	{
		// Check that incenteev-parameters are empty after removing the only file
		self::$manipulator->removeParameterFiles([ 'app/config/parameters.yml', 'app/config/foobar.yml' ]);
		$this->assertEmpty(self::$manipulator->getParameterFiles());
	}
	
	/**
	 * @covers ::removeParameterFiles()
	 * @testdox removeParameterFiles() removes parameter files from flat array
	 */
	public function test_removeParameterFiles_with_flat_array()
	{
		// Remove the require section
		$config = $this->readAttribute(self::$manipulator, 'config');
		$config['extra']['incenteev-parameters'] = [ 'file' => 'app/config/parameters.yml' ];
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that incenteev-parameters are empty after removing the only file
		self::$manipulator->removeParameterFiles([ 'app/config/parameters.yml' ]);
		$this->assertEmpty(self::$manipulator->getParameterFiles());
	}
	
	/**
	 * @covers ::addParameterFiles()
	 * @testdox addParameterFiles() adds parameter files
	 */
	public function test_addParameterFiles()
	{
		// Check that incenteev-parameters content is correct after adding a file
		self::$manipulator->addParameterFiles([ 'foo/config/parameters.yml' ]);
		$this->assertEquals([
			'app/config/parameters.yml',
			'app/config/foobar.yml',
			'foo/config/parameters.yml',
		], self::$manipulator->getParameterFiles());
	}
	
	/**
	 * @covers ::addParameterFiles()
	 * @testdox addParameterFiles() adds incenteev-parameters section with files
	 */
	public function test_addParameterFiles_add_section()
	{
		$config = $this->readAttribute(self::$manipulator, 'config');
		$config['extra'] = [];
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that incenteev-parameters content is correct after adding a file
		self::$manipulator->addParameterFiles([ 'foo/config/parameters.yml' ]);
		$this->assertEquals([
			'foo/config/parameters.yml',
		], self::$manipulator->getParameterFiles());
	}
	
	/**
	 * @covers ::addParameterFiles()
	 * @testdox addParameterFiles() adds parameter files to flat array
	 */
	public function test_addParameterFiles_with_flat_array()
	{
		$config = $this->readAttribute(self::$manipulator, 'config');
		$config['extra']['incenteev-parameters'] = [ 'file' => 'app/config/parameters.yml' ];
		$this->writeAttribute(self::$manipulator, 'config', $config);
		
		// Check that incenteev-parameters content is correct after adding a file
		self::$manipulator->addParameterFiles([ 'foo/config/parameters.yml' ]);
		$this->assertEquals([
			'app/config/parameters.yml',
			'foo/config/parameters.yml',
		], self::$manipulator->getParameterFiles());
	}
	
	/**
	 * Data provider for test_getComposerContentHash().
	 *
	 * @return array
	 */
	public function provide_test_getComposerContentHash_data()
	{
		return [
			'' => [
				null,
				null,
				null
			],
			'with platform settings' => [
				[
					'php' => '7.1',
					'ext-foobar' => '4.0'
				],
				null,
				null
			],
		];
	}
	
	/**
	 * @covers ::getComposerContentHash()
	 * @dataProvider provide_test_getComposerContentHash_data
	 * @param null|array $platform Sample platform setting
	 * @testdox getComposerContentHash() returns correct checksum
	 */
	public function test_getComposerContentHash($platform)
	{
		// Calculate the expected hash
		$config = $this->readAttribute(self::$manipulator, 'config');
		
		if (null !== $platform) {
			$config['config']['platform'] = $platform;
			$this->writeAttribute(self::$manipulator, 'config', $config);
		}
		
		$relevantKeys = [
			'name',
			'version',
			'require',
			'require-dev',
			'conflict',
			'replace',
			'provide',
			'minimum-stability',
			'prefer-stable',
			'repositories',
			'extra',
		];
		
		$relevantComposerConfig = [];
		
		foreach (array_intersect($relevantKeys, array_keys($config)) as $key) {
			$relevantComposerConfig[$key] = $config[$key];
		}
		
		if (isset($config['config']['platform'])) {
			$relevantComposerConfig['config']['platform'] = $config['config']['platform'];
		}
		
		ksort($relevantComposerConfig);
		
		$hash = md5(json_encode($relevantComposerConfig));
		
		// Check that getComposerContentHash() returns a md5 hash over the relevant key in composer.json
		$this->assertEquals($hash, $this->callMethod(self::$manipulator, 'getComposerContentHash'));
	}
	
	/**
	 * @covers ::getFilename()
	 * @testdox getFilename() returns correct path
	 */
	public function test_getFilename()
	{
		$this->assertEquals(self::$fixturesDir . '/composer.json', self::$manipulator->getFilename());
	}
	
	/**
	 * Data provider for test_save().
	 *
	 * @return array
	 */
	public function provide_test_save_data()
	{
		return [
			'' =>
				[
					null,
					null,
					null
				],
			'with hash in composer.lock' => [
				'hash',
				null,
				null
			],
			'with content-hash in composer.lock' => [
				'content-hash',
				null,
				null
			],
		];
	}
	
	/**
	 * @covers ::save()
	 * @dataProvider provide_test_save_data
	 * @testdox save() produces correct output file
	 */
	public function test_save($key)
	{
		// Test with composer.lock
		if (null !== $key) {
			$lockFilename = self::$fixturesDir . '/composer.lock';
			self::getFs()->dumpFile($lockFilename, json_encode([ $key => md5('foobar') ]));
		}
		
		$kernels = iterator_to_array(Finder::create()->files()->name('*Kernel.php')->notName('BootKernel.php')->depth(1)->in(self::$fixturesDir));
		$caches = iterator_to_array(Finder::create()->files()->name('*Cache.php')->depth(1)->in(self::$fixturesDir));
		
		// Do the same modifications a MultikernelConvertCommand does
		$parameterFiles = self::$manipulator->getParameterFiles();
		
		self::$manipulator->removeFromClassmap(array_map(function(SplFileInfo $file) {
			return $file->getRelativePathname();
		}, array_merge($kernels, $caches)));
		
		if ( null === $key) {
			self::$manipulator->addRequirements([
				'motana/multikernel' => '~1.0',
			]);
		}
		
		self::$manipulator->addToClassMap([
			'apps/BootKernel.php'
		])
		->removeParameterFiles($parameterFiles)
		->addParameterFiles(array_merge([
			'apps/config/parameters.yml',
		], array_map(function($file) {
			return 'apps/' . $file;
		}, $parameterFiles)))
		->save();
		
		// Serialize the config to compare with
		$config = json_encode($this->readAttribute(self::$manipulator, 'config'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
		
		// Check that the file actually has been updated
		$this->assertEquals($config, file_get_contents(self::$manipulator->getFilename()));
		
		// Check the action output its message
		$messages = [];
		$messages[] = '  updated ' . self::$fixturesDir . '/composer.json';
		if (null !== $key) {
			$messages[] = '  updated ' . self::$fixturesDir . '/composer.lock';
		}
		$this->assertEquals(implode("\n", $messages) . "\n", self::$output->fetch());
		
		// Check the composer.lock contains the correct hash
		if (null !== $key) {
			$lockFile = json_decode(file_get_contents($lockFilename), true);
			switch ($key) {
				case 'hash': $this->assertEquals(md5($config), $lockFile['hash']); break;
				case 'content-hash': $this->assertEquals($this->callMethod(self::$manipulator, 'getComposerContentHash'), $lockFile['content-hash']); break;
			}
		}
	}
}
