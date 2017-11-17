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
use Motana\Bundle\MultikernelBundle\Manipulator\KernelManipulator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestListener;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Manipulator\KernelManipulator
 * @testdox Motana\Bundle\MultikernelBundle\Manipulator\KernelManipulator
 */
class KernelManipulatorTest extends TestCase
{
	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static $fixturesDir;
	
	/**
	 * Path of the kernel file to test with.
	 *
	 * @var string
	 */
	protected static $file;
	
	/**
	 * The kernel to manipulate.
	 *
	 * @var KernelInterface
	 */
	protected static $kernel;
	
	/**
	 * The manipulator to test.
	 *
	 * @var KernelManipulator
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
		self::$fixturesDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/manipulator/kernel';
		self::$file = self::$fixturesDir . '/ManipulatorKernel.php';

		// Override the output of the generators
		self::$output = new BufferedOutput();
		self::writeAttribute(Generator::class, 'output', self::$output);
		
		// Generate the kernel to test with
		$generator = new FixtureGenerator();
		$generator->generateKernelClass(self::$file, 'ManipulatorKernel', [
			'uses' => [
				[ 'class' => 'Foo\\Bar' ],
				[ 'class' => 'Foo\\Baz', 'alias' => 'Bazinga' ],
			],
			'bundle' => false,
			'bundles' => [
				'SwiftmailerBundle' => false,
				'DoctrineBundle' => false,
				'SensioFrameworkExtraBundle' => false,
				'MotanaMultikernelBundle' => false,
				'SensioDistributionBundle' => false,
			],
		]);
		
		// Load the generated kernel
		include(self::$file);
		
		// Fetch all output
		self::$output->fetch();
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$kernel = new \ManipulatorKernel('test', false);
		self::$manipulator = new KernelManipulator(self::$kernel);
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Read the file
		$lines = file(self::$file);
		
		// Check the properties are initialized correctly
		$this->assertEquals($lines, $this->readAttribute(self::$manipulator, 'lines'));
		$this->assertEquals(count($lines), $this->readAttribute(self::$manipulator, 'lineCount'));
		
		// Check the parent constructor has been called
		$this->assertSame(self::$kernel, $this->readAttribute(self::$manipulator, 'kernel'));
		
		$reflected = $this->readAttribute(self::$manipulator, 'reflected');
		/** @var \ReflectionObject $reflected */
		
		$this->assertInstanceOf(\ReflectionObject::class, $reflected);
		$this->assertEquals('ManipulatorKernel', $reflected->getName());
	}
	
	/**
	 * @covers ::removeMethods()
	 * @testdox removeMethods() removes methods
	 */
	public function test_removeMethods()
	{
		// Remove the same methods the MultikernelConvertCommand would remove
		$methods = [
			'getCacheDir',
			'getLogDir',
			'notExistingMethod',
			'registerContainerConfiguration',
		];
		
		// Remove the methods
		self::$manipulator->removeMethods($methods);
		
		// Get processed content
		$content = implode('', $this->readAttribute(self::$manipulator, 'lines'));
		
		// Check the methods are removed
		foreach ($methods as $method) {
			$this->assertNotContains('public function ' . $method . '(', $content);
		}
	}
	
	/**
	 * @covers ::replaceUses()
	 * @testdox replaceUses() replaces and removes use clauses
	 */
	public function test_replaceUses()
	{
		// Replace the same use clauses the MultikernelConvertCommand would replace
		$uses = [
			'Symfony\\Component\\HttpKernel\\Kernel' => 'Motana\\Bundle\\MultikernelBundle\\HttpKernel\\Kernel',
			'Symfony\\Component\\Config\\Loader\\LoaderInterface' => null,
			'Foo\\Baz' => null,
		];
		
		// Replace the use clauses
		self::$manipulator->replaceUses($uses);
		
		// Get processed content
		$content = implode('', $this->readAttribute(self::$manipulator, 'lines'));
		
		// Check the use clauses are actually replaced
		foreach ($uses as $oldClassOrNamespace => $newClassOrNamespace) {
			$this->assertNotContains('use ' . $oldClassOrNamespace . ';', $content);
			if (null !== $newClassOrNamespace) {
				$this->assertContains('use ' . $newClassOrNamespace . ';', $content);
			}
		}
	}
	
	/**
	 * @covers ::save()
	 * @testdox save() produces correct output
	 */
	public function test_save()
	{
		// Remove the same methods the MultikernelConvertCommand would remove
		$methods = [
			'getCacheDir',
			'getLogDir',
			'registerContainerConfiguration',
		];
		
		// Replace the same use clauses the MultikernelConvertCommand would replace
		$uses = [
			'Symfony\\Component\\HttpKernel\\Kernel' => 'Motana\\Bundle\\MultikernelBundle\\HttpKernel\\Kernel',
			'Symfony\\Component\\Config\\Loader\\LoaderInterface' => null,
		];
		
		// Call save without doing any modifications first
		self::$manipulator->save();
		
		// Check there is no output indicating a file was saved
		$this->assertEmpty(self::$output->fetch());
		
		// Modify the kernel
		self::$manipulator->removeMethods($methods);
		self::$manipulator->replaceUses($uses);
		
		// Save the modifications
		self::$manipulator->save();
		
		// Check there is output indicating a file was saved
		$this->assertEquals('  updated ' . self::$file . "\n", self::$output->fetch());
		
		// Get the content of the modified file
		$content = file_get_contents(self::$file);
		
		// Check the methods are removed
		foreach ($methods as $method) {
			$this->assertNotContains('public function ' . $method . '(', $content);
		}
		
		// Check the use clauses are replaced
		foreach ($uses as $oldClassOrNamespace => $newClassOrNamespace) {
			$this->assertNotContains('use ' . $oldClassOrNamespace . ';', $content);
			if (null !== $newClassOrNamespace) {
				$this->assertContains('use ' . $newClassOrNamespace . ';', $content);
			}
		}
	}
}
