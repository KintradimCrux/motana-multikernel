<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Descriptor;

use Motana\Bundle\MultikernelBundle\Console\Descriptor\TextDescriptor;
use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Descriptor\TextDescriptor
 * @testdox Motana\Bundle\MultikernelBundle\Console\Descriptor\TextDescriptor
 */
class TextDescriptorTest extends ApplicationTestCase
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
	 * @var TextDescriptor
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
			$dir = __DIR__;
			while ( ! is_file($dir . '/composer.json')) {
				$dir = dirname($dir);
			}
			self::$projectDir = $dir;
		}
		
		self::$descriptor = new TextDescriptor();
		
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
		
		$this->writeAttribute(self::$descriptor, 'output', self::$output);
		
		if (false === $fake) {
			self::$command = self::$application->find('help');
		} else {
			self::$command = new Command('test');
			self::$command->setDescription('TextDescriptor test command');
			self::$command->setHelp(<<<EOH
Tests the TextDescriptor.
					
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
	 * @covers ::writeText()
	 * @testdox writeText() returns correct output
	 */
	public function test_writeText()
	{
		// Output formatted text
		$this->callMethod(self::$descriptor, 'writeText', '<info>test</info>', [ 'raw_output' => true ]);
		
		// Check the output is correct
		$this->assertEquals('<info>test</info>', self::$output->fetch());
		
		// Output raw text
		$this->callMethod(self::$descriptor, 'writeText', '<info>test</info>', [ 'raw_text' => true, 'raw_output' => true ]);
		
		// Check the output is correct
		$this->assertEquals('test', self::$output->fetch());
	}
	
	/**
	 * @covers ::getCommandAliasesText()
	 * @testdox getCommandAliasesText() returns correct output
	 */
	public function test_getCommandAliasesText()
	{
		// Add aliases to the command
		self::$command->setAliases([ 'dummy:test', 'alias:test' ]);
		
		// Check the correct text is returned
		$this->assertEquals('[dummy:test|alias:test] ', $this->callMethod(self::$descriptor, 'getCommandAliasesText', self::$command));
	}
	
	/**
	 * Data provider for test_formatDefaultValue().
	 *
	 * @return array
	 */
	public function provide_test_formatDefaultValue_data()
	{
		return [
			'a NULL value' => [
				'null',
				null
			],
			'an Integer' => [
				'1',
				1
			],
			'a Boolean' => [
				'false',
				false
			],
			'a String' => [
				'"test"',
				'test'
			],
			'a String ending with a backslash' => [
				'"Test string that ends with backslash\u0000"',
				"Test string that ends with backslash\\"
			],
			'an Array of Integers' => [
				'[1,2,3]',
				[
					1,
					2,
					3
				]
			],
			'an Array of Strings' => [
				'["one","two","three"]',
				[
					'one',
					'two',
					'three'
				]
			],
			'an Object' => [
				'{}',
				new \stdClass()
			],
		];
	}
	
	/**
	 * @covers ::formatDefaultValue()
	 * @dataProvider provide_test_formatDefaultValue_data
	 * @testdox formatDefaultValue() returns correct output for
	 */
	public function test_formatDefaultValue($expectedResult, $value)
	{
		// Check the expected value is returned
		$this->assertEquals($expectedResult, $this->callMethod(self::$descriptor, 'formatDefaultValue', $value));
	}
	
	/**
	 * @covers ::getColumnWidth()
	 * @testdox getColumnWidth() returns the correct width
	 */
	public function test_getColumnWidth()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false);
		
		// Add an alias to the help command
		self::$application->get('help')->setAliases([ 'help:help' ]);
		
		// Calculate the column width
		$width = 0;
		$commands = self::$application->all();
		foreach ($commands as $command) {
			/** @var Command $command */
			$width = max($width, Helper::strlen($command->getName()));
			foreach ($command->getAliases() as $alias) {
				$width = max($width, Helper::strlen($alias));
			}
		}
		$width += 2;
		
		// Check the correct width is returned
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'getColumnWidth', $commands));
	}
	
	/**
	 * @covers ::calculateTotalWidthForArguments()
	 * @testdox calculateTotalWidthForArguments() returns the correct width
	 */
	public function test_calculateTotalWidthForArguments()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false);
		
		// Calculate the width of arguments
		$width = 0;
		$arguments = self::$application->getDefinition()->getArguments();
		foreach ($arguments as $argument) {
			/** @var InputArgument $argument */
			$width = max($width, Helper::strlen($argument->getName()));
		}
		
		// Check the correct width is returned
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'calculateTotalWidthForArguments', $arguments));
	}
	
	/**
	 * @covers ::calculateTotalWidthForShortcuts()
	 * @testdox calculateTotalWidthForShortcuts() returns the correct width
	 */
	public function test_calculateTotalWidthForShortcuts()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false);
		
		// Calculate the width of shortcuts
		$width = 0;
		$options = self::$application->getDefinition()->getOptions();
		foreach ($options as $option) {
			/** @var InputOption $option */
			$width = max($width, 1 + Helper::strlen($option->getShortcut()));
		}
		$width += 2;
		
		// Check the correct width is returned
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'calculateTotalWidthForShortcuts', $options));
	}
	
	/**
	 * @covers ::calculateTotalWidthForOptions()
	 * @testdox calculateTotalWidthForOptions() returns the correct width
	 */
	public function test_calculateTotalWidthForOptions()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the app environment
		$this->setUp(false);
		
		// Calculate the with of options
		$options = self::$application->getDefinition()->getOptions();
		$shortcutWidth = $this->callMethod(self::$descriptor, 'calculateTotalWidthForShortcuts', $options);
		
		$width = 0;
		foreach ($options as $option) {
			/** @var InputOption $option */
			$nameLength = $shortcutWidth + 4 + Helper::strlen($option->getName());
			if ($option->acceptValue()) {
				$valueLength = 1 + Helper::strlen($option->getName());
				$valueLength += $option->isValueOptional() ? 2 : 0;
				$nameLength += $valueLength;
			}
			
			$width = max($width, $nameLength);
		}
		
		// Check the correct width is returned
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'calculateTotalWidthForOptions', $options));
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
	 * Data provider for test_getCommandData().
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
			'a MultikernelApplication (raw text)' => [
				null,
				'application_multikernel',
				[
					'raw_text' => true
				]
			],
			'a MultikernelApplication (raw text, namespace debug)' => [
				null,
				'application_multikernel',
				[
					'namespace' => 'debug',
					'raw_text' => true
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
			'an Application (raw text)' => [
				'app',
				'application_appkernel',
				[
					'raw_text' => true
				]
			],
			'an Application (raw text, namespace debug)' => [
				'app',
				'application_appkernel',
				[
					'namespace' => 'debug',
					'raw_text' => true
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
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format (default: txt)
	 * @param boolean $generateTemplates Boolean indicating to generate templates
	 * @return string
	 */
	protected static function getTemplate($case, array $options = [], $format = 'txt', $generateTemplates = false)
	{
		// Append options to template basename
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		// Get the project directory
		$projectDir = realpath(__DIR__ . '/../../..');
		
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
					$projectDir,
				], [
					'{{ kernel_version }}',
					'console {{ kernel_name }}',
					'console {{ kernel_name }}',
					'(kernel: {{ kernel_name }},',
					'(kernel: {{ kernel_name }},',
					'{{ project_dir }}',
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
			'project_dir' => $projectDir,
		]);
	}
}
