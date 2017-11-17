<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Manipulator;

use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator
 * @testdox Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator
 */
class FilesystemManipulatorTest extends TestCase
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
	 * @var FilesystemManipulator
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
	public static function setUpTestEnvironment()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}

		// Set the fixtures directory for the tests
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/manipulator/fs';

		// Create the fixtures directory
		self::$fs = new Filesystem();
		self::getFs()->mkdir(self::$fixturesDir);

		// Override the output of the generators
		self::$output = new BufferedOutput();

		// Create a filesystem manipulator instance
		self::$manipulator = new FilesystemManipulator();
	}

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		// Override the output used for generators and manipulators
		self::writeAttribute(Generator::class, 'output', self::$output);
		self::writeAttribute(FilesystemManipulator::class, 'output', self::$output);

		// Fetch all output
		self::$output->fetch();

		// Change to the fixtures directory
		chdir(self::$fixturesDir);
	}

	/**
	 * @covers ::relativizePath()
	 * @testdox relativizePath() returns a path relative to the current working directory
	 */
	public function test_relativizePath()
	{
		// Create a subdirectory in it
		self::getFs()->mkdir(self::$fixturesDir . '/subdir');

		// Check the correct relative path is returned
		$this->assertEquals('./subdir/', $this->callStaticMethod(FilesystemManipulator::class, 'relativizePath', self::$fixturesDir . '/subdir'));
	}

	/**
	 * @covers ::write()
	 * @testdox write() prints messages to output
	 */
	public function test_write()
	{
		// Write a message
		$this->callStaticMethod(FilesystemManipulator::class, 'write', 'this is a message');

		// Check the output contains the message
		$this->assertEquals('this is a message', self::$output->fetch());
	}

	/**
	 * @covers ::write()
	 * @testdox write() creates a ConsoleOutput instance if required
	 */
	public function test_write_creates_output()
	{
		// Fake an environment not capable of using php://stdout
		$ostype = getenv('OSTYPE');
		putenv('OSTYPE=OS400');

		// Clear the output property of the manipulator class
		$this->writeAttribute(FilesystemManipulator::class, 'output', null);

		// Write a message
		ob_start();
		$this->callStaticMethod(FilesystemManipulator::class, 'write', 'this is a message');
		$content = ob_get_clean();

		// Check the output contains the message
		$this->assertEquals('this is a message', $content);

		// Restore the previous OSTYPE environment variable
		putenv('OSTYPE=' . $ostype);
	}

	/**
	 * @covers ::dumpFile()
	 * @testdox dumpFile() creates files
	 */
	public function test_dumpFile()
	{
		// Write a test file
		self::$manipulator->dumpFile(self::$fixturesDir . '/subdir/dumped_file', 'content');

		// Check the manipulator prints the expected message
		$this->assertEquals("  created ./subdir/dumped_file\n", self::$output->fetch());

		// Check the copied file is actually there
		$this->assertFileExists(self::$fixturesDir . '/subdir/dumped_file');
	}

	/**
	 * @covers ::dumpFile()
	 * @testdox dumpFile() overwrites files
	 */
	public function test_dumpFile_overwrites_file()
	{
		// Write a test file
		self::$manipulator->dumpFile(self::$fixturesDir . '/subdir/dumped_file', 'content');

		// Check the manipulator prints the expected message
		$this->assertEquals("  updated ./subdir/dumped_file\n", self::$output->fetch());
	}

	/**
	 * @covers ::copy()
	 * @testdox copy() actually copies files
	 */
	public function test_copy()
	{
		// Create a test file to copy
		self::getFs()->dumpFile(self::$fixturesDir . '/subdir/testfile', 'content');

		// Copy the file
		self::$manipulator->copy(self::$fixturesDir . '/subdir/testfile', self::$fixturesDir . '/subdir/testfile_copy');

		// Check the manipulator prints the expected message
		$this->assertEquals("  created ./subdir/testfile_copy\n", self::$output->fetch());

		// Check the copied file is actually there
		$this->assertFileExists(self::$fixturesDir . '/subdir/testfile_copy');
	}

	/**
	 * @covers ::copy()
	 * @expectedException Symfony\Component\Filesystem\Exception\FileNotFoundException
	 * @expectedExceptionMessageRegExp |^Failed to copy "(.*)" because file does not exist.$|
	 * @testdox copy() throws a FileNotFoundException for non-existing files
	 */
	public function test_copy_with_not_existing_file()
	{
		// Copy the file
		self::$manipulator->copy(self::$fixturesDir . '/not_existing_file', self::$fixturesDir . '/not_existing_file_copy');
	}

	/**
	 * @covers ::copy()
	 * @testdox copy() does not overwrite newer files if not requested
	 */
	public function test_copy_does_not_overwrite_newer_file()
	{
		// Touch the test file to make it older than its copy
		self::getFs()->touch(self::$fixturesDir . '/subdir/testfile', time() - 10);

		// Copy the file
		self::$manipulator->copy(self::$fixturesDir . '/subdir/testfile', self::$fixturesDir . '/subdir/testfile_copy');

		// Check the manipulator printed no message
		$this->assertEmpty(self::$output->fetch());
	}

	/**
	 * @covers ::copy()
	 * @testdox copy() does overwrites newer files if requested
	 */
	public function test_copy_overwrites_newer_file()
	{
		// Copy the file
		self::$manipulator->copy(self::$fixturesDir . '/subdir/testfile', self::$fixturesDir . '/subdir/testfile_copy', true);

		// Check the manipulator prints the expected message
		$this->assertEquals("  updated ./subdir/testfile_copy\n", self::$output->fetch());
	}

	/**
	 * @covers ::mirror()
	 * @testdox mirror() copies directory structures
	 */
	public function test_mirror()
	{
		// Copy the directory containing test files
		self::$manipulator->mirror(self::$fixturesDir . '/subdir', self::$fixturesDir . '/subdir_copy');

		// Check the output contains the correct messages
		$this->assertEquals(<<<EOC
  created ./subdir_copy/
  created ./subdir_copy/dumped_file
  created ./subdir_copy/testfile
  created ./subdir_copy/testfile_copy

EOC
		, self::$output->fetch());

		// Check the copied files exist
		$this->assertFileExists(self::$fixturesDir . '/subdir_copy/dumped_file');
		$this->assertFileExists(self::$fixturesDir . '/subdir_copy/testfile_copy');
		$this->assertFileExists(self::$fixturesDir . '/subdir_copy/testfile');
	}

	/**
	 * @covers ::mkdir()
	 * @testdox mkdir() creates directories
	 */
	public function test_mkdir()
	{
		// Create a directory
		self::$manipulator->mkdir(self::$fixturesDir . '/subdir/mkdir');

		// Check the manipulator generated correct output
		$this->assertEquals("  created ./subdir/mkdir/\n", self::$output->fetch());

		// Check the directory exists
		$this->assertDirectoryExists(self::$fixturesDir . '/subdir/mkdir');

		// Call mkdir() again with the same directory name
		self::$manipulator->mkdir(self::$fixturesDir . '/subdir/mkdir');

		// Check the manipulator generated no output
		$this->assertEmpty(self::$output->fetch());
	}

	/**
	 * @covers ::remove()
	 * @testdox remove() removes files and directories
	 */
	public function test_remove()
	{
		// Remove a previously created copy of a directory
		self::$manipulator->remove(self::$fixturesDir . '/subdir_copy');

		// Check the output contains the correct messages
		$this->assertEquals(<<<EOC
  removed ./subdir_copy/testfile_copy
  removed ./subdir_copy/testfile
  removed ./subdir_copy/dumped_file
  removed ./subdir_copy/

EOC
		, self::$output->fetch());
	}

	/**
	 * @covers ::symlink()
	 * @testdox symlink() creates and updates symlinks
	 */
	public function test_symlink()
	{
		// Create a symlink to a previously created test directory
		self::$manipulator->symlink(self::$fixturesDir . '/subdir', self::$fixturesDir . '/subdir_symlink');

		// Check the manipulator generated correct output
		$this->assertEquals("  created ./subdir_symlink/\n", self::$output->fetch());

		// Check the symlink exists and is a symlink
		$this->assertDirectoryExists(self::$fixturesDir . '/subdir_symlink');
		$this->assertTrue(is_link(self::$fixturesDir . '/subdir_symlink'));
		$this->assertEquals(self::$fixturesDir . '/subdir', readlink(self::$fixturesDir . '/subdir_symlink'));

		// Update the previously created symlink
		self::$manipulator->symlink(self::$fixturesDir . '/subdir', self::$fixturesDir . '/subdir_symlink');

		// Check the manipulator generated correct output
		$this->assertEquals("  updated ./subdir_symlink/\n", self::$output->fetch());
	}
}
