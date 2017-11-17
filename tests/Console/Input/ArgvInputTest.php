<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Input;

use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Console\Input\ConditionalKernelArgument;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput
 * @testdox Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput
 */
class ArgvInputTest extends TestCase
{
	/**
	 * @var ArgvInput
	 */
	protected static $input;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($argv = null)
	{
		if (null === $argv) {
			$argv = [ 'bin/console', '-edev', '-f=dev', '-l', '-', '-q', '--no-debug', '--', 'boot', 'cache:pool:clear', '' ];
		}
		
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = current($argv);
		$_SERVER['argv'] = $argv;
		
		self::$input = new ArgvInput();
		$this->setObjectAttribute(self::$input, 'definition', $this->getDefaultInputDefinition());
	}
	
	/**
	 * Gets the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		return new InputDefinition([
			new ConditionalKernelArgument('kernel', InputArgument::OPTIONAL, 'The kernel to execute', [ 'boot', 'app' ]),
			new InputArgument('command', InputArgument::OPTIONAL, 'The command to execute'),
			
			new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
			new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
			new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
			new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
			new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
			new InputOption('--no-debug', '', InputOption::VALUE_NONE, 'Disable debug mode'),
			new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
			new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'Environment'),
			new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Files to process'),
			new InputOption('--log', '-l', InputOption::VALUE_OPTIONAL, 'Log filename (- for stdout)'),
		]);
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Get commandline parameters
		$argv = $_SERVER['argv'];
		
		// Remove the first parameter
		array_shift($argv);
		
		// Check the tokens property is initialized correctly
		$this->assertEquals($argv, $this->readAttribute(self::$input, 'tokens'));
		
		// Check that the parent constructor has been called
		$this->assertEquals([], $this->readAttribute(self::$input, 'parsed'));
		$this->assertInstanceOf(InputDefinition::class, $this->readAttribute(self::$input, 'definition'));
		$this->assertNull($this->readAttribute(self::$input, 'stream'));
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
		$this->assertEquals([], $this->readAttribute(self::$input, 'arguments'));
		$this->assertTrue($this->readAttribute(self::$input, 'interactive'));
	}
	
	/**
	 * @covers ::getFirstArgument()
	 * @testdox getFirstArgument() returns the kernel name
	 */
	public function test_getFirstArgument()
	{
		// Check the correct first argument is returned
		$this->assertEquals('boot', self::$input->getFirstArgument());
	}
	
	/**
	 * @covers ::getFirstArgument()
	 * @testdox getFirstArgument() returns NULL when no arguments are specified
	 */
	public function test_getFirstArgument_without_arguments()
	{
		// Set up a commandline without any tokens
		$this->setUp([ 'bin/console' ]);
		
		// Check there is no first argument
		$this->assertNull(self::$input->getFirstArgument());
	}
	
	/**
	 * @covers ::shift()
	 * @testdox shift() removes the command name
	 */
	public function test_shift()
	{
		// Set up the input with some more arguments
		self::$input->shift();
		
		// Check that shift() removed the first token
		$this->assertEquals([ '-edev', '-f=dev', '-l', '-', '-q', '--no-debug', '--', 'cache:pool:clear', '' ], $this->readAttribute(self::$input, 'tokens'));
	}
	
	/**
	 * Data provider for test_hasParameterOption().
	 *
	 * @return array
	 */
	public function provide_test_hasParameterOption_data()
	{
		return [
			'TRUE for a specified option shortcut with value' => [
				true,
				['-e', '--env'],
				false
			],
			'TRUE for a specified array option shortcut with value' => [
				true,
				['-f', '--file'],
				false
			],
			'TRUE for a specified option shortcut with value in next token' => [
				true,
				['-l'],
				false
			],
			'TRUE for a specified option shortcut without value' => [
				true,
				['-q'],
				false
			],
			'TRUE for a specified option' => [
				true,
				['--no-debug'],
				false
			],
			'TRUE for a specified argument' => [
				true,
				['boot', ''],
				false
			],
			'FALSE for a not specified argument' => [
				false,
				'boot',
				true
			],
			'FALSE for a not specified option' => [
				false,
				'what?',
				false
			]
		];
	}
	
	/**
	 * @covers ::hasParameterOption()
	 * @dataProvider provide_test_hasParameterOption_data
	 * @param boolean $expectedResult Expected return value of hasParameterOption()
	 * @param string|array $parameter Parameter or array of parameters
	 * @param boolean $onlyParams Boolean indicating to process no arguments
	 * @testdox hasParameterOption() returns
	 */
	public function test_hasParameterOption($expectedResult, $values, $onlyParams)
	{
		// Check the expected result is returned
		$this->assertEquals($expectedResult, self::$input->hasParameterOption($values, $onlyParams));
	}
	
	/**
	 * Data provider for test_getParameterOption().
	 *
	 * @return array
	 */
	public function provide_test_getParameterOption_data()
	{
		return [
			'specified option values' => [
				'dev',
				['-e', '--env'],
				'',
				false
			],
			'specified array option values' => [
				'dev',
				['-f', '--file'],
				'',
				false
			],
			'specified option values from next token' => [
				'-',
				['-l', '--log'],
				'',
				false
			],
			'the default value for specified option shortcuts without value' => [
				false,
				'-q',
				false,
				false
			],
			'the default value for specified options without value' => [
				false,
				'--no-debug',
				false,
				false
			],
			'specified arguments' => [
				'boot',
				['boot', ''],
				'',
				false
			],
			'the default value for not specified arguments' => [
				false,
				'boot',
				false,
				true
			],
			'the default value for not specified options' => [
				false,
				'what?',
				false,
				false
			],
		];
	}
	
	/**
	 * @covers ::getParameterOption()
	 * @dataProvider provide_test_getParameterOption_data
	 * @param string $expectedResult Expected return value of getParameterOption()
	 * @param string|array $values Values to search for
	 * @param string $default Default value
	 * @param boolean $onlyParams Boolean indicating to process no arguments
	 * @testdox getParameterOption() returns
	 */
	public function test_getParameterOption($expectedResult, $values, $default, $onlyParams)
	{
		// Check the expected result is returned
		$this->assertEquals($expectedResult, self::$input->getParameterOption($values, $default, $onlyParams));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--test" option does not exist.
	 * @testdox addLongOption() throws a RuntimeException for undefined options
	 */
	public function test_addLongOption_with_undefined_option()
	{
		// Check an exception is thrown when trying to add a a non-existing option
		$this->callMethod(self::$input, 'addLongOption', 'test', '1');
	}
	
	/**
	 * @covers ::addLongOption()
	 * @testdox addLongOption() with value sets options accepting a value
	 */
	public function test_addLongOption_with_value()
	{
		// Add a long option with value
		$this->callMethod(self::$input, 'addLongOption', 'env', 'dev');
		
		// Check the option is actually set
		$this->assertEquals([ 'env' => 'dev' ], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--ansi" option does not accept a value.
	 * @testdox addLongOption() throws a RuntimeException when specifying a value for an option not accepting one
	 */
	public function test_addLongOption_with_not_accepted_value()
	{
		// Check an exception is thrown when a long option does not accept a value
		$this->callMethod(self::$input, 'addLongOption', 'ansi', 'color');
	}
	
	/**
	 * @covers ::addLongOption()
	 * @testdox addLongOption() without a value sets options not accepting a value
	 */
	public function test_addLongOption_without_value()
	{
		// Add a long option without value
		$this->callMethod(self::$input, 'addLongOption', 'ansi', null);
		
		// Check the option is actually set
		$this->assertEquals([ 'ansi' => true ], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 * @testdox addLongOption() throws a RuntimeException when a required value is not specified
	 */
	public function test_addLongOption_without_required_value()
	{
		// Check an exception is thrown when trying to add a long option without value that requires one
		$this->callMethod(self::$input, 'addLongOption', 'env', null);
	}
	
	/**
	 * @covers ::addLongOption()
	 * @testdox addLongOption() adds array option values
	 */
	public function test_addLongOption_with_array_option()
	{
		// Add the same long option twice with different filenames
		$this->callMethod(self::$input, 'addLongOption', 'file', 'file1');
		$this->callMethod(self::$input, 'addLongOption', 'file', 'file2');
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
				'file2',
			]
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @testdox addLongOption() adds array option values from next token
	 */
	public function test_addLongOption_with_array_option_value_from_next_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ 'file1' ]);
		
		// Add a long option with a NULL token
		$this->callMethod(self::$input, 'addLongOption', 'file', null);
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
			]
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @testdox addLongOption() does not add array option values from a NULL token
	 */
	public function test_addLongOption_with_array_option_value_from_NULL_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ null ]);
		
		// Add a long option with a NULL token
		$this->callMethod(self::$input, 'addLongOption', 'file', null);
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @testdox addLongOption() does not add array option values from an option token
	 */
	public function test_addLongOption_with_array_option_value_from_option_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ '-edev' ]);
		
		// Add a long option with a NULL token
		$this->callMethod(self::$input, 'addLongOption', 'file', null);
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-t" option does not exist.
	 * @testdox addShortOption() throws a RuntimeException for undefined options
	 */
	public function test_addShortOption_with_undefined_option()
	{
		// Check an exception is thrown when trying to set a non-existing short option
		$this->callMethod(self::$input, 'addShortOption', 't', '1');
	}
	
	/**
	 * @covers ::addShortOption()
	 * @testdox addShortOption() with a value sets options accepting a value
	 */
	public function test_addShortOption_with_value()
	{
		// Add a short option with value
		$this->callMethod(self::$input, 'addShortOption', 'e', 'dev');
		
		// Check the option is actually set
		$this->assertEquals([
			'env' => 'dev'
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @expectedException RuntimeException
	 * @expectedException The "--no-interaction" option does not accept a value.
	 * @testdox addShortOption() throws a RuntimeException when specifying a value for an option not accepting one
	 */
	public function test_addShortOption_with_not_accepted_value()
	{
		// Check an exception is thrown when trying to set a short option with a not accepted value
		$this->callMethod(self::$input, 'addShortOption', 'n', 'value');
	}
	
	/**
	 * @covers ::addShortOption()
	 * @testdox addShortOption() without a value sets options not accepting a value
	 */
	public function test_addShortOption_without_value()
	{
		// Set a short option with no value
		$this->callMethod(self::$input, 'addShortOption', 'n', null);
		
		// Check the option is actually set
		$this->assertEquals([
			'no-interaction' => true,
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 * @testdox addShortOption() throws a RuntimeException when a required value is not specified
	 */
	public function test_addShortOption_without_required_value()
	{
		// Check an exception is thrown when trying to set a short option without its required value
		$this->callMethod(self::$input, 'addShortOption', 'e', null);
	}
	
	/**
	 * @covers ::addShortOption()
	 * @testdox addShortOption() adds array option values
	 */
	public function test_addShortOption_with_array_option()
	{
		// Add the same short option twice with different filenames
		$this->callMethod(self::$input, 'addShortOption', 'f', 'file1');
		$this->callMethod(self::$input, 'addShortOption', 'f', 'file2');
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
				'file2',
			]
		], $this->readAttribute(self::$input, 'options'));
		
	}
	
	/**
	 * @covers ::addShortOption()
	 * @testdox addShortOption() adds array option values from next token
	 */
	public function test_addShortOption_with_array_option_value_from_next_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ 'file1' ]);
		
		// Add a short option with a NULL token
		$this->callMethod(self::$input, 'addShortOption', 'f', null);
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
			]
		], $this->readAttribute(self::$input, 'options'));
		
	}
	
	/**
	 * @covers ::addShortOption()
	 * @testdox addShortOption() does not add array option values from a NULL token
	 */
	public function test_addShortOption_with_array_option_value_from_NULL_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ null ]);
		
		// Add a short option with a NULL token
		$this->callMethod(self::$input, 'addShortOption', 'f', null);
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @testdox addShortOption() does not add array option values from an option token
	 */
	public function test_addShortOption_with_array_option_value_from_option_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ '-edev' ]);
		
		// Add a short option with a NULL token
		$this->callMethod(self::$input, 'addShortOption', 'f', null);
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @testdox parseArgument() sets the kernel argument from a token matching a kernel name
	 */
	public function test_parseArgument_with_kernel_name()
	{
		// Parse an argument matching a kernel name
		$this->callMethod(self::$input, 'parseArgument', 'app');
		
		// Check the kernel argument is actually set
		$this->assertEquals([
			'kernel' => 'app',
		], $this->readAttribute(self::$input, 'arguments'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @testdox parseArgument() does not set the kernel argument from a token matching no kernel name
	 */
	public function test_parseArgument_with_invalid_kernel_name()
	{
		// Parse an argument not matching any kernel name
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Check the kernel argument contains NULL
		$this->assertEquals([
			'kernel' => null,
		], $this->readAttribute(self::$input, 'arguments'));
		
		// Check the token is added to parsed tokens
		$this->assertEquals([
			'cache:pool:clear'
		], $this->readAttribute(self::$input, 'parsed'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @testdox parseArgument() sets the command argument from a token
	 */
	public function test_parseArgument_with_command_name()
	{
		// Parse an argument matching a command
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Reset the parsed tokens array
		$this->writeAttribute(self::$input, 'parsed', []);
		
		// Parse the argument again
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Check the kernel argument is NULL and the command argument is set
		$this->assertEquals([
			'kernel' => null,
			'command' => 'cache:pool:clear',
		], $this->readAttribute(self::$input, 'arguments'));
		
		// Check the parsed tokens array is empty
		$this->assertEquals([], $this->readAttribute(self::$input, 'parsed'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Too many arguments, expected arguments: "kernel" "command".
	 * @testdox parseArgument() throws a RuntimeException when too many arguments are specified
	 */
	public function test_parseArgument_with_too_many_arguments()
	{
		// Parse an argument matching a command
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Reset the parsed tokens array
		$this->writeAttribute(self::$input, 'parsed', []);
		
		// Parse the argument again
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Check an exception is thrown when trying to parse another argument
		$this->callMethod(self::$input, 'parseArgument', 'toomany');
	}
	
	/**
	 * @covers ::parseArgument()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage No arguments expected, got "cache:pool:clear".
	 * @testdox parseArgument() throws a RuntimeException when arguments are specified and no arguments are expected
	 */
	public function test_parseArgument_with_no_arguments_expected()
	{
		// Set up an empty input definition
		$this->writeAttribute(self::$input, 'definition', new InputDefinition());
		
		// Check an exception is thrown when no arguments are expected
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
	}
	
	/**
	 * @covers ::parseArgument()
	 * @testdox parseArgument() adds array argument values
	 */
	public function test_parseArgument_with_array_argument()
	{
		// Add an array argument to the definition
		$definition = $this->getDefaultInputDefinition();
		$definition->addArgument(new InputArgument('files', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Files to process'));
		$this->writeAttribute(self::$input, 'definition', $definition);

		// Parse an argument matching a command
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Reset the parsed tokens array
		$this->writeAttribute(self::$input, 'parsed', []);
		
		// Parse the argument again
		$this->callMethod(self::$input, 'parseArgument', 'cache:pool:clear');
		
		// Parse two more arguments for the array argument
		$this->callMethod(self::$input, 'parseArgument', 'file1');
		$this->callMethod(self::$input, 'parseArgument', 'file2');
		
		// Check the arguments are set correctly
		$this->assertEquals([
			'kernel' => null,
			'command' => 'cache:pool:clear',
			'files' => [
				'file1',
				'file2',
			]
		], $this->readAttribute(self::$input, 'arguments'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--unknown" option does not exist.
	 * @testdox parseLongOption() throws a RuntimeException for undefined options
	 */
	public function test_parseLongOption_with_undefined_option()
	{
		$this->callMethod(self::$input, 'parseLongOption', '--unknown');
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @testdox parseLongOption() with a value sets options accepting a value
	 */
	public function test_parseLongOption_with_value()
	{
		// Parse a long option with value
		$this->callMethod(self::$input, 'parseLongOption', '--env=dev');
		
		// Check the option is actually set
		$this->assertEquals([
			'env' => 'dev',
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--ansi" option does not accept a value.
	 * @testdox parseLongOption() throws a RuntimeException when specifying a value for an option not accepting one
	 */
	public function test_parseLongOption_with_not_accepted_value()
	{
		// Check an exception is thrown when trying to parse a long option with a not accepted value
		$this->callMethod(self::$input, 'parseLongOption', '--ansi=color');
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @testdox parseLongOption() without a value sets options not accepting a value
	 */
	public function test_parseLongOption_without_value()
	{
		// Parse a long option without a value
		$this->callMethod(self::$input, 'parseLongOption', '--ansi');
		
		// Check the option is actually set
		$this->assertEquals([
			'ansi' => true,
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 * @testdox parseLongOption() throws a RuntimeException when a required value is not specified
	 */
	public function test_parseLongOption_with_missing_required_value()
	{
		// Check an exception is thrown when trying to set a long option without its required value
		$this->callMethod(self::$input, 'parseLongOption', '--env');
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @testdox parseLongOption() adds array option values
	 */
	public function test_parseLongOption_with_array_option()
	{
		// Add the same long option twice with different filenames
		$this->callMethod(self::$input, 'parseLongOption', '--file=file1');
		$this->callMethod(self::$input, 'parseLongOption', '--file=file2');
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
				'file2',
			]
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @testdox parseLongOption() adds array option values from next token
	 */
	public function test_parseLongOption_with_array_option_value_from_next_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ 'file1' ]);
		
		// Parse a long option with no value
		$this->callMethod(self::$input, 'parseLongOption', '--file');
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
			]
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @testdox parseLongOption() does not add array option values from a NULL token
	 */
	public function test_parseLongOption_with_array_option_value_from_NULL_token()
	{
		// Preset parsed tokens for thhe test
		$this->writeAttribute(self::$input, 'parsed', [ null ]);
		
		// Parse a long option with an empty value
		$this->callMethod(self::$input, 'parseLongOption', '--file=');
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @testdox parseLongOption() does not add array option values from an option token
	 */
	public function test_parseLongOption_with_array_option_value_from_option_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ '-edev' ]);
		
		// Parse a long option without a value
		$this->callMethod(self::$input, 'parseLongOption', '--file', null);
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @testdox parseShortOption() with a value sets options accepting a value
	 */
	public function test_parseShortOption_with_value()
	{
		// Parse a short option with value
		$this->callMethod(self::$input, 'parseShortOption', '-e=dev');
		
		// Check the option is actually set
		$this->assertEquals([
			'env' => 'dev',
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-1" option does not exist.
	 * @testdox parseShortOption() throws a RuntimeException when specifying a value for an option not accepting one
	 */
	public function test_parseShortOption_with_not_accepted_value()
	{
		// Check an exception is thrown when trying to set a short option with a not accepted value
		$this->callMethod(self::$input, 'parseShortOption', '-n1');
	}

	/**
	 * @covers ::parseShortOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-n" option accepts no value.
	 * @testdox parseShortOption() throws a RuntimeException when specifying a value for an option not accepting one ("-n=" notation)
	 */
	public function test_parseShortOption_with_not_accepted_value_after_equal_sign()
	{
		// Check an exception is thrown when trying to set a short option with a not accepted value
		$this->callMethod(self::$input, 'parseShortOption', '-n=1');
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @testdox parseShortOption() without a value sets options not accepting a value
	 */
	public function test_parseShortOption_without_value()
	{
		// Parse a short option with no value
		$this->callMethod(self::$input, 'parseShortOption', '-n');
		
		// Check the option is actually set
		$this->assertEquals([
			'no-interaction' => true,
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 * @testdox parseShortOption() throws a RuntimeException when a required value is not specified
	 */
	public function test_parseShortOption_with_missing_required_value()
	{
		// Check an exception is thrown when trying to set a short option without its required value
		$this->callMethod(self::$input, 'parseShortOption', '-e');
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @testdox parseShortOption() adds array option values
	 */
	public function test_parseShortOption_with_array_option()
	{
		// Set the same short option twice with different filenames
		$this->callMethod(self::$input, 'parseShortOption', '-f=file1');
		$this->callMethod(self::$input, 'parseShortOption', '-f=file2');
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
				'file2',
			]
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @testdox parseShortOption() adds array option values from next token
	 */
	public function test_parseShortOption_with_array_option_value_from_next_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ 'file1' ]);
		
		// Parse a short option
		$this->callMethod(self::$input, 'parseShortOption', '-f');
		
		// Check the option is actually set
		$this->assertEquals([
			'file' => [
				'file1',
			]
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @testdox parseShortOption() does not add array option values from a NULL token
	 */
	public function test_parseShortOption_with_array_option_value_from_NULL_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ null ]);
		
		// Parse a short option with an empty value
		$this->callMethod(self::$input, 'parseShortOption', '-f=');
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @testdox parseShortOption() does not add array option values from an option token
	 */
	public function test_parseShortOption_with_array_option_value_from_option_token()
	{
		// Preset parsed tokens for the test
		$this->writeAttribute(self::$input, 'parsed', [ '-edev' ]);
		
		// Parse a short option with no value
		$this->callMethod(self::$input, 'parseShortOption', '-f', null);
		
		// Check the option is not set
		$this->assertEquals([], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @testdox parseShortOptionSet() without a value sets options not accepting a value
	 */
	public function test_parseShortOptionSet_without_value_sets_option_not_accepting_a_value()
	{
		// Parse a short option set
		$this->callMethod(self::$input, 'parseShortOptionSet', 'n');
		
		// Check the options have been set
		$this->assertEquals([
			'no-interaction' => true,
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @testdox parseShortOptionSet() with a value sets options accepting a value
	 */
	public function test_parseShortOptionSet_with_value_sets_option_accepting_a_value()
	{
		// Parse a short option set
		$this->callMethod(self::$input, 'parseShortOptionSet', 'edev');
		
		// Check the options have been set
		$this->assertEquals([
			'env' => 'dev',
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @testdox parseShortOptionSet() sets multiple options without and with value
	 */
	public function test_parseShortOptionSet_with_options()
	{
		// Parse a short option set
		$this->callMethod(self::$input, 'parseShortOptionSet', 'nqvedev');
		
		// Check the options have been set
		$this->assertEquals([
			'no-interaction' => true,
			'quiet' => true,
			'verbose' => true,
			'env' => 'dev',
		], $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-1" option does not exist.
	 * @testdox parseShortOptionSet() throws a RuntimeException for a not accepted value
	 */
	public function test_parseShortOptionSet_with_not_accepted_value()
	{
		// Check an exception is thrown when trying to parse a short option set containing a not accepted value
		$this->callMethod(self::$input, 'parseShortOptionSet', 'n1');
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-n" option accepts no value.
	 * @testdox parseShortOptionSet() throws a RuntimeException for not accepted values after an equal sign
	 */
	public function test_parseShortOptionSet_with_not_accepted_value_after_equal_sign()
	{
		// Check an exception is thrown when trying to parse a short option set containing a not accepted value
		$this->callMethod(self::$input, 'parseShortOptionSet', 'n=1');
	}
	
	/**
	 * @covers ::parse()
	 * @testdox parse() sets arguments and options
	 */
	public function test_parse_sets_arguments_and_options()
	{
		// Add an argument to the input definition
		$definition = $this->getDefaultInputDefinition();
		$definition->addArgument(new InputArgument('files', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Files to process'));
		
		// Bind input to the definition
		self::$input->bind($definition);
		
		// Check the returned arguments are correct
		$this->assertEquals([
			'kernel' => 'boot',
			'command' => 'cache:pool:clear',
			'files' => [
				''
			],
		], self::$input->getArguments());
		
		// Check the returned options are correct
		$this->assertEquals([
			'help' => false,
			'quiet' => true,
			'verbose' => false,
			'version' => false,
			'ansi' => false,
			'no-ansi' => false,
			'no-debug' => true,
			'no-interaction' => false,
			'env' => 'dev',
			'log' => '-',
			'file' => [
				'dev',
			]
		], self::$input->getOptions());
	}
	
	/**
	 * @covers ::parse()
	 * @testdox parse() sets the command argument from empty argument token
	 */
	public function test_parse_empty_command_argument_token()
	{
		// Set up a commandline with no arguments except for an empty one
		$this->setUp([ 'bin/console', '-edev', '', '--' ]);
		
		// Bind input to the definition
		self::$input->bind($this->getDefaultInputDefinition());
		
		// Check the returned arguments are correct
		$this->assertEquals([
			'kernel' => null,
			'command' => '',
		], self::$input->getArguments());
		
		// Check the returned options are correct
		$this->assertEquals([
			'help' => false,
			'quiet' => false,
			'verbose' => false,
			'version' => false,
			'ansi' => false,
			'no-ansi' => false,
			'no-debug' => false,
			'no-interaction' => false,
			'env' => 'dev',
			'log' => null,
			'file' => []
		], self::$input->getOptions());
	}
	
	/**
	 * @covers ::__toString()
	 * @testdox __toString() returns the formatted commandline
	 */
	public function test_toString()
	{
		// Check the returned string representation is correct
		$this->assertEquals('-edev -f=dev -l - -q --no-debug -- boot \'cache:pool:clear\' ',(string) self::$input);
	}
}
