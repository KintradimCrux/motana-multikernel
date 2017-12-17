<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Descriptor;

use Motana\Bundle\MultikernelBundle\Command\HelpCommand;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor
 * @testdox Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor
 */
class DescriptorTest extends TestCase
{
	/**
	 * The project directory.
	 *
	 * @var string
	 */
	protected static $projectDir;
	
	/**
	 * The descriptor to test.
	 *
	 * @var Descriptor
	 */
	protected static $descriptor;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set the fixtures directory for the tests
		self::$projectDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/project';
		
		$container = new ContainerBuilder();
		$container->setParameter('kernel.project_dir', self::$projectDir);
		
		self::$descriptor = new DescriptorTestDummy();
		self::$descriptor->setContainer($container);
	}
	
	/**
	 * Data provider for test_describe().
	 *
	 * @return array
	 */
	public function provide_test_describe_data()
	{
		return [
			'an InputArgument' => [
				'InputArgument',
				new InputArgument('test', InputArgument::REQUIRED, 'An argument'),
				null,
				null,
			],
			'an InputOption' => [
				'InputOption',
				new InputOption('test', null, InputOption::VALUE_OPTIONAL, 'An option'),
				null,
				null,
			],
			'an InputDefinition' => [
				'InputDefinition',
				new InputDefinition(),
				null,
				null,
			],
			'a Command' => [
				'Command',
				new HelpCommand(),
				null,
				null,
			],
			'an Application' => [
				'Application',
				new Application($this->getMockForAbstractClass(Kernel::class, ['dev', false])),
				null,
				null,
			],
			'an invalid object' => [
				null,
				new \stdClass(),
				'InvalidArgumentException',
				'Object of type "stdClass" is not describable.'
			]
		];
	}
	
	/**
	 * @covers ::describe()
	 * @dataProvider provide_test_describe_data
	 * @param string $objectType The object type
	 * @param mixed $object The object to describe
	 * @param string|null $expectedException Expected exception type
	 * @param string|null $expectedExceptionMessage Expected exception message
	 * @testdox describe() calls the correct method for
	 */
	public function test_describe($objectType, $object, $expectedException = null, $expectedExceptionMessage = null)
	{
		// Expect an exception if the exception parameters are set
		if ($expectedException && $expectedExceptionMessage) {
			$this->expectException($expectedException);
			$this->expectExceptionMessage($expectedExceptionMessage);
		}
		
		// Describe the object
		self::$descriptor->describe(new BufferedOutput(), $object);
		
		// Check the correct method has been called
		$this->assertArrayHasKey($objectType, self::$descriptor->methodCalled);
		$this->assertEquals(true, self::$descriptor->methodCalled[$objectType]);
	}
	
	/**
	 * @covers ::write()
	 * @testdox write() adds content to the output instance
	 */
	public function test_write()
	{
		// Set the output property of the descriptor to a new BufferedOutput
		$output = new BufferedOutput();
		$this->writeAttribute(self::$descriptor, 'output', $output);
		
		// Call the method to write content
		$this->callMethod(self::$descriptor, 'write', 'content');
		
		// Check the content has been written
		$this->assertEquals('content', $output->fetch());
	}
	
	/**
	 * @covers ::makePathRelative()
	 * @testdox makePathRelative() returns a path relative to project directory
	 */
	public function test_makePathRelative()
	{
		// Check the returned path is correct
		$this->assertEquals('./tests/Controller', $this->callMethod(self::$descriptor, 'makePathRelative', self::$projectDir . '/tests/Controller'));
	}
	
	/**
	 * @covers ::getProcessedHelp()
	 * @testdox getProcessedHelp() removes 'php' from command names
	 */
	public function test_getProcessedHelp()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = self::$projectDir . '/bin/console';
		
		// Create a new command
		$command = new Command('test');
		
		// Set the command help
		$command->setHelp(<<<EOH
%command.name%
php %command.full_name%
EOH
		);
		
		// Check the output is correct
		$expected = <<<EOH
test
./bin/console test
EOH;
		$this->assertEquals($expected, $this->callMethod(self::$descriptor, 'getProcessedHelp', $command));
	}
}

/**
 * Dummy descriptor for some of the tests.
 */
class DescriptorTestDummy extends Descriptor
{
	/**
	 * An array holding the information which methods have been called.
	 *
	 * @var array
	 */
	public $methodCalled = [];
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor::describeInputArgument()
	 */
	protected function describeInputArgument(InputArgument $argument, array $options = [])
	{
		$this->methodCalled['InputArgument'] = true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor::describeInputOption()
	 */
	protected function describeInputOption(InputOption $option, array $options = [])
	{
		$this->methodCalled['InputOption'] = true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor::describeInputDefinition()
	 */
	protected function describeInputDefinition(InputDefinition $definition, array $options = [])
	{
		$this->methodCalled['InputDefinition'] = true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor::describeCommand()
	 */
	protected function describeCommand(Command $command, array $options = [])
	{
		$this->methodCalled['Command'] = true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor::describeApplication()
	 */
	protected function describeApplication(Application $application, array $options = [])
	{
		$this->methodCalled['Application'] = true;
	}
}
