<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Command\Validators;
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\Validators
 * @testdox Motana\Bundle\MultikernelBundle\Command\Validators
 */
class ValidatorsTest extends TestCase
{
	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static $fixturesDir;
	
	/**
	 * Output of the generators and the filesystem manipulator.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * Boolean indicating to show output of the generators and the filesystem manipulator.
	 *
	 * @var boolean
	 */
	protected static $debug = false;
	
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/default';
	}
	
	/**
	 * @covers ::validateNewKernelName()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage The kernel name contains invalid characters.
	 * @testdox validateNewKernelName() throws an InvalidArgumentException for invalid kernel names
	 */
	public function test_validateNewKernelName_with_invalid_kernel_name()
	{
		// Check an exception is thrown when an invalid kernel name is specified
		Validators::validateNewKernelName('$secret', self::$fixturesDir);
	}
	
	/**
	 * Data provider for test_validateNewKernelName_with_forbidden_kernel_name().
	 *
	 * @return array
	 */
	public function provide_test_validateNewKernelName_with_forbidden_kernel_name_data()
	{
		return [
			'(\'boot\')' => [
				'boot'
			],
			'(\'config\')' => [
				'config'
			],
		];
	}
	
	/**
	 * @covers ::validateNewKernelName()
	 * @dataProvider provide_test_validateNewKernelName_with_forbidden_kernel_name_data
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage The kernel name is not allowed.
	 * @param string $kernelName Kernel name to test
	 * @testdox validateNewKernelName() throws an InvalidArgumentException for forbidden kernel names
	 */
	public function test_validateNewKernelName_with_forbidden_kernel_name($kernelName)
	{
		// Check an exception is thrown when a forbidden kernel name is specified
		Validators::validateNewKernelName($kernelName, self::$fixturesDir);
	}
	
	/**
	 * @covers ::validateNewKernelName()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage The kernel name is already in use.
	 * @testdox validateNewKernelName() throws an InvalidArgumentException for existing kernel names
	 */
	public function test_validateNewKernelName_with_existing_kernel_name()
	{
		// Check an exception is thrown when validating an already existing kernel name
		Validators::validateNewKernelName('app', self::$fixturesDir);
	}
	
	/**
	 * @covers ::validateNewKernelName()
	 * @testdox validateNewKernelName() returns valid new kernel names
	 */
	public function test_validateNewKernelName_with_valid_kernel_name()
	{
		// Check the kernel name is returned
		$this->assertEquals('foo', Validators::validateNewKernelName('foo', self::$fixturesDir));
	}
	
	/**
	 * @covers ::validateRelativePath()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage The path must be relative to the project directory
	 * @testdox validateRelativePath() throws an InvalidArgumentException for absolute paths
	 */
	public function test_validateRelativePath_with_absolute_path()
	{
		Validators::validateRelativePath('/tmp');
	}
	
	/**
	 * @covers ::validateRelativePath()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage The path must be relative to the project directory
	 * @testdox validateRelativePath() throws an InvalidArgumentException for paths containing '../'
	 */
	public function test_validateRelativePath_with_directory_traversal()
	{
		Validators::validateRelativePath('src/../../../../tmp');
	}
	
	/**
	 * @covers ::validateRelativePath()
	 * @testdox validateRelativePath() returns valid paths
	 */
	public function test_validateRelativePath_returns_path()
	{
		$this->assertEquals('src/', Validators::validateRelativePath('src/'));
	}
}
