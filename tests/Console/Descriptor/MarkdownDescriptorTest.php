<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Descriptor;

use Motana\Bundle\MultikernelBundle\Console\Descriptor\MarkdownDescriptor;
use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Descriptor\MarkdownDescriptor
 * @testdox Motana\Bundle\MultikernelBundle\Console\Descriptor\MarkdownDescriptor
 */
class MarkdownDescriptorTest extends ApplicationTestCase
{
	/**
	 * The project directory.
	 *
	 * @var string
	 */
	protected static $projectDir;
	
	/**
	 * Descriptor to test.
	 *
	 * @var MarkdownDescriptor
	 */
	protected static $descriptor;
	
	/**
	 * Descriptor output.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * Command to test with.
	 *
	 * @var Command
	 */
	protected static $command;
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase::setUp()
	 */
	protected function setUp($fake = true, $app = null, $environment = 'test', $debug = false)
	{
		if (null === self::$projectDir) {
			$dir = self::$fixturesDir;
			while ( ! is_file($dir . '/composer.json')) {
				$dir = dirname($dir);
			}
			self::$projectDir = $dir;
		}
		
		if (null !== self::$projectDir) {
			chdir(self::$projectDir);
		}
		
		self::$descriptor = new MarkdownDescriptor();
		
		if (false === $fake) {
			parent::setUp($app, $environment, $debug);
			self::$application->getKernel()->boot();
			self::$descriptor->setContainer(self::$application->getKernel()->getContainer());
		} else {
			self::$application = null;
			$container = new ContainerBuilder();
			$container->setParameter('kernel.project_dir', self::$projectDir);
			self::$descriptor->setContainer($container);
		}
		
		self::$output = new BufferedOutput();
		
		if (false === $fake) {
			self::$command = self::$application->find('help');
		} else {
			self::$command = new Command('test');
			self::$command->setDescription('JsonDescriptor test command');
			self::$command->setHelp(<<<EOH
Tests the <info>MarkDownDescriptor</info>.

<comment>Usage</comment>:
%command.name%
php %command.full_name%
EOH
					);
			self::$command->setDefinition([
				new InputArgument('method', InputArgument::OPTIONAL, $description = 'Test method'),
				new InputOption('--all', '-a', InputOption::VALUE_NONE, 'Run all tests'),
				new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'File to process'),
				new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			]);
		}
	}
	
	/**
	 * @covers ::formatDescription()
	 * @testdox formatDescription() returns correct output
	 */
	public function test_formatDescription()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Check the description is correct
		$this->assertEquals($this->getTemplate('description'), $this->callMethod(self::$descriptor, 'formatDescription', self::$command->getDescription()));
		
		// Get processed help
		$help = $this->callMethod(self::$descriptor, 'getProcessedHelp', self::$command);
		
		// Check the output is correct
		$this->assertEquals($this->getTemplate('help'), $this->callMethod(self::$descriptor, 'formatDescription', $help));
	}
	
	/**
	 * @covers ::describeInputArgument()
	 * @testdox describeInputArgument() returns correct output
	 */
	public function test_describeInputArgument()
	{
		// Describe the argument
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getArgument(0));
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate('argument'), self::$output->fetch());
	}

	/**
	 * @covers ::describeInputArgument()
	 * @testdox describeInputArgument() returns correct output for an argument with default value
	 */
	public function test_describeInputArgument_with_default_value()
	{
		// Add a default value to the argument
		self::$command->getDefinition()->getArgument(0)->setDefault('standard');
		
		// Describe the argument
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getArgument(0));
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate('argument_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputOption()
	 * @testdox describeInputOption() returns correct output
	 */
	public function test_describeInputOption()
	{
		// Describe the option
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('all'));
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate('option'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputOption()
	 * @testdox describeInputOption() returns correct output for an option with default value
	 */
	public function test_describeInputOption_with_default_value()
	{
		// Add a default value to the option
		self::$command->getDefinition()->getOption('file')->setDefault('autoexec.bat');
		
		// Describe the option
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('file'));
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate('option_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputDefinition()
	 * @testdox describeInputDefinition() returns correct output
	 */
	public function test_describeInputDefinition_returns_correct_output()
	{
		// Describe the definition
		self::$descriptor->describe(self::$output, self::$command->getDefinition());
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate('definition'), self::$output->fetch());
	}
	
	/**
	 * Data provider for test_describeCommand().
	 *
	 * @return array
	 */
	public function provide_test_describeCommand_data()
	{
		return [
			'MultikernelApplication' => [
				null,
				'command_multikernel',
				[]
			],
			'Application' => [
				'app',
				'command_appkernel',
				[]
			],
		];
	}
	
	/**
	 * @covers ::describeCommand()
	 * @dataProvider provide_test_describeCommand_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $options Display options
	 * @testdox describeCommand() returns correct output for
	 */
	public function test_describeCommand($app, $template, array $options = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false, $app);
		
		// Describe the command
		self::$descriptor->describe(self::$output, self::$command);
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}

	/**
	 * Data provider for test_describeApplication().
	 *
	 * @return array
	 */
	public function provide_test_describeApplication_data()
	{
		return [
			'a MultikernelApplication' => [
				null,
				'application_multikernel',
				[]
			],
			'a MultikernelApplication (namespace debug)' => [
				null,
				'application_multikernel',
				[
					'namespace' => 'debug'
				]
			],
			'an Application' => [
				'app',
				'application_appkernel',
				[]
			],
			'an Application (namespace debug)' => [
				'app',
				'application_appkernel',
				[
					'namespace' => 'debug'
				]
			],
		];
	}
	
	/**
	 * @covers ::describeApplication()
	 * @dataProvider provide_test_describeApplication_data
	 * @param string $app App subdirectory name
	 * @param string $template Template file name
	 * @param array $options Display options
	 * @testdox describeApplication() returns correct output for
	 */
	public function test_describeApplication($app, $template, $options)
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false, $app);
		
		// Describe the application
		self::$descriptor->describe(self::$output, self::$application, $options);
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeApplication()
	 * @testdox describeApplication() removes aliases and empty namespaces
	 */
	public function test_describeApplication_removes_aliases_and_empty_namespaces()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false, 'app');
		
		// Add an alias to the help command
		self::$application->add(self::$application->get('help')->setAliases([ 'help:help' ]));
		
		// Describe the application
		self::$descriptor->describe(self::$output, self::$application, []);
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate('application_appkernel', [ 'alias' => 'help' ]), self::$output->fetch());
	}
	
	/**
	 * @covers ::describe()
	 * @expectedException Symfony\Component\Console\Exception\InvalidArgumentException
	 * @expectedExceptionMessage Object of type "stdClass" is not describable.
	 * @testdox describe() throws an InvalidArgumentException for unsupported objects
	 */
	public function test_describe_with_unsupported_object()
	{
		// Check an exception is thrown when trying to describe an invalid object
		self::$descriptor->describe(self::$output, new \stdClass());
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format (default: md)
	 * @param boolean $generateTemplates Boolean indicating to generate templates
	 * @return string
	 */
	protected static function getTemplate($case, array $options = [], $format = 'md', $generateTemplates = false)
	{
		// Return small templates directly
		switch ($case) {
			case 'description':
				return 'JsonDescriptor test command';
			case 'help':
				return <<<EOD
Tests the `MarkDownDescriptor`.

Usage:
test
./bin/console test
EOD;
		}
		
		// Append options to template basename
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		// Insert twig variables into output and save it to a sample file when requested
		if ($generateTemplates) {
			$output = clone(self::$output);
			$content = $output->fetch();
			if ( ! empty($content)) {
				$content = str_replace([
					\Symfony\Component\HttpKernel\Kernel::VERSION,
					'console boot',
					'console app',
					'(kernel: boot,',
					'(kernel: app,',
				], [
					'{{ kernel_version }}',
					'console {{ kernel_name }}',
					'console {{ kernel_name }}',
					'(kernel: {{ kernel_name }},',
					'(kernel: {{ kernel_name }},',
				], $content);
				$content = preg_replace([
					'#/tmp/motana_multikernel_tests_[^/]+/#',
				], [
					'{{ fixture_dir }}/',
				], $content);
				self::getFs()->dumpFile(__DIR__ . '/../../../src/Resources/fixtures/descriptor/' . $format . '/' . $case . '.' . $format . '.twig', $content);
			}
		}
		
		// Generate expected content from a template
		$generator = new FixtureGenerator();
		return $generator->generateDescriptorOutput($case, $format, [
			'kernel_name' => false !== strpos($case, 'multikernel') ? 'boot' : 'app',
		]);
	}
}
