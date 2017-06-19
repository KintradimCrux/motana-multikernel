<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Console\Input;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Console\Input\KernelArgument;
use Motana\Bundle\MultikernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput
 */
class ArgvInputTest extends TestCase
{
	/**
	 * @var ArgvInput
	 */
	protected static $input;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($argv = null)
	{
		if (null === $argv) {
			$argv = array('bin/console', '-edev', '-f=dev', '-l', '-', '-q', '--no-debug', '--', 'boot', 'debug:config', '');
		}
		
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
		return new InputDefinition(array(
			new KernelArgument('kernel', InputArgument::OPTIONAL, 'The kernel to execute', array('boot', 'app')),
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
		));
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$argv = $_SERVER['argv'];
		array_shift($argv);
		
		// Check the tokens property is initialized correctly
		$this->assertEquals($argv, $this->readAttribute(self::$input, 'tokens'));
		
		// Check that the parent constructor has been called
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'parsed'));
		$this->assertInstanceOf(InputDefinition::class, $this->readAttribute(self::$input, 'definition'));
		$this->assertNull($this->readAttribute(self::$input, 'stream'));
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'arguments'));
		$this->assertTrue($this->readAttribute(self::$input, 'interactive'));
	}
	
	/**
	 * @covers ::getFirstArgument()
	 */
	public function testGetFirstArgument()
	{
		$this->assertEquals('boot', self::$input->getFirstArgument());
	}
	
	/**
	 * @covers ::getFirstArgument()
	 * @depends testGetFirstArgument
	 */
	public function testFirstArgumentNoTokens()
	{
		$this->setUp(array('bin/console'));
		
		$this->assertNull(self::$input->getFirstArgument());
	}
	
	/**
	 * @covers ::shift()
	 */
	public function testShift()
	{
		// Set up the input with some more arguments
		self::$input->shift();
		
		// Check that shift() removed the first token
		$this->assertEquals(array('-edev', '-f=dev', '-l', '-', '-q', '--no-debug', '--', 'debug:config', ''), $this->readAttribute(self::$input, 'tokens'));
	}
	
	/**
	 * Data provider for testHasParameterOption().
	 * 
	 * @return array
	 */
	public function provide_testHasParameterOption_data()
	{
		return array(
			array(true, ['-e', '--env'], false),
			array(true, ['-f', '--file'], false),
			array(true, ['-l'], false),
			array(true, ['-q'], false),
			array(true, ['--no-debug'], false),
			array(true, ['boot', ''], false),
			array(false, 'boot', true),
			array(false, 'what?', false)
		);
	}
	
	/**
	 * @covers ::hasParameterOption
	 * @dataProvider provide_testHasParameterOption_data
	 * @param boolean $expectedResult Expected return value of hasParameterOption()
	 * @param string|array $parameter Parameter or array of parameters
	 * @param boolean $onlyParams Boolean indicating to process no arguments
	 */
	public function testHasParameterOption($expectedResult, $values, $onlyParams)
	{
		$this->assertEquals($expectedResult, self::$input->hasParameterOption($values, $onlyParams));
	}
	
	/**
	 * Data provider for testGetParameterOption().
	 *
	 * @return array
	 */
	public function provide_testGetParameterOption_data()
	{
		return array(
			array('dev', ['-e', '--env'], '', false),
			array('dev', ['-f', '--file'], '', false),
			array('-', ['-l', '--log'], '', false),
			array(false, '-q', false, false),
			array(false, '--no-debug', false, false),
			array('boot', ['boot', ''], '', false),
			array(false, 'boot', false, true),
			array(false, 'what?', false, false),
		);
	}
	
	/**
	 * @covers ::getParameterOption()
	 * @dataProvider provide_testGetParameterOption_data
	 * @param string $expectedResult Expected return value of getParameterOption()
	 * @param string|array $values Values to search for
	 * @param string $default Default value
	 * @param boolean $onlyParams Boolean indicating to process no arguments
	 */
	public function testGetParameterOption($expectedResult, $values, $default, $onlyParams)
	{
		$this->assertEquals($expectedResult, self::$input->getParameterOption($values, $default, $onlyParams));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--test" option does not exist.
	 */
	public function testAddUnknownLongOption()
	{
		$this->callMethod(self::$input, 'addLongOption', 'test', '1');
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddUnknownLongOption
	 */
	public function testAddLongOptionWithValue()
	{
		$this->callMethod(self::$input, 'addLongOption', 'env', 'dev');
		
		$this->assertEquals(array('env' => 'dev'), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongOptionWithValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--ansi" option does not accept a value.
	 */
	public function testAddLongOptionWithNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'addLongOption', 'ansi', 'color');
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongOptionWithNotAcceptedValue
	 */
	public function testAddLongOptionWithoutValue()
	{
		$this->callMethod(self::$input, 'addLongOption', 'ansi', null);
		
		$this->assertEquals(array('ansi' => true), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongOptionWithoutValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 */
	public function testAddLongOptionWithoutRequiredValue()
	{
		$this->callMethod(self::$input, 'addLongOption', 'env', null);
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongOptionWithoutRequiredValue
	 */
	public function testAddLongArrayOption()
	{
		$this->callMethod(self::$input, 'addLongOption', 'file', 'autoexec.bat');
		$this->callMethod(self::$input, 'addLongOption', 'file', 'config.sys');
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
				'config.sys',
			)
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongArrayOption
	 */
	public function testAddLongArrayOptionParseNextValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array('autoexec.bat'));
		
		$this->callMethod(self::$input, 'addLongOption', 'file', null);
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
			)
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongArrayOptionParseNextValue
	 */
	public function testAddLongArrayOptionParseNoNullValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array(null));
		
		$this->callMethod(self::$input, 'addLongOption', 'file', null);
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addLongOption()
	 * @depends testAddLongArrayOptionParseNoNullValue
	 */
	public function testAddLongArrayOptionParseNoOption()
	{
		$this->writeAttribute(self::$input, 'parsed', array('-edev'));
		
		$this->callMethod(self::$input, 'addLongOption', 'file', null);
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-t" option does not exist.
	 */
	public function testAddUnknownShortOption()
	{
		$this->callMethod(self::$input, 'addShortOption', 't', '1');
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddUnknownShortOption
	 */
	public function testAddShortOptionValue()
	{
		$this->callMethod(self::$input, 'addShortOption', 'e', 'dev');
		
		$this->assertEquals(array(
			'env' => 'dev'
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddUnknownShortOption
	 * @expectedException RuntimeException
	 * @expectedException The "--no-interaction" option does not accept a value.
	 */
	public function testAddShortOptionWithNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'addShortOption', 'n', 'value');
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddShortOptionWithNotAcceptedValue
	 */
	public function testAddShortOptionWithoutValue()
	{
		$this->callMethod(self::$input, 'addShortOption', 'n', null);
		
		$this->assertEquals(array(
			'no-interaction' => true,
		), $this->readAttribute(self::$input, 'options'));
	}
	
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddShortOptionWithoutValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 */
	public function testAddShortOptionWithoutRequiredValue()
	{
		$this->callMethod(self::$input, 'addShortOption', 'e', null);
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddShortOptionWithoutRequiredValue
	 */
	public function testAddShortArrayOption()
	{
		$this->callMethod(self::$input, 'addShortOption', 'f', 'autoexec.bat');
		$this->callMethod(self::$input, 'addShortOption', 'f', 'config.sys');
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
				'config.sys',
			)
		), $this->readAttribute(self::$input, 'options'));
		
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddShortArrayOption
	 */
	public function testAddShortArrayOptionParseNextValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array('autoexec.bat'));
		
		$this->callMethod(self::$input, 'addShortOption', 'f', null);
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
			)
		), $this->readAttribute(self::$input, 'options'));
		
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddShortArrayOptionParseNextValue
	 */
	public function testAddShortArrayOptionParseNoNullValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array(null));
		
		$this->callMethod(self::$input, 'addShortOption', 'f', null);
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::addShortOption()
	 * @depends testAddShortArrayOptionParseNoNullValue
	 */
	public function testAddShortArrayOptionParseNoOption()
	{
		$this->writeAttribute(self::$input, 'parsed', array('-edev'));
		
		$this->callMethod(self::$input, 'addShortOption', 'f', null);
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseArgument()
	 */
	public function testParseKernelArgument()
	{
		$this->callMethod(self::$input, 'parseArgument', 'app');
		
		$this->assertEquals(array(
			'kernel' => 'app',
		), $this->readAttribute(self::$input, 'arguments'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @depends testParseKernelArgument
	 */
	public function testParseNonKernelArgument()
	{
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		
		$this->assertEquals(array(
			'kernel' => null,
		), $this->readAttribute(self::$input, 'arguments'));
		
		$this->assertEquals(array(
			'debug:config'
		), $this->readAttribute(self::$input, 'parsed'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @depends testParseNonKernelArgument
	 */
	public function testParseCommandArgument()
	{
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		$this->writeAttribute(self::$input, 'parsed', array());
		
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		
		$this->assertEquals(array(
			'kernel' => null,
			'command' => 'debug:config',
		), $this->readAttribute(self::$input, 'arguments'));
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'parsed'));
	}
	
	/**
	 * @covers ::parseArgument()
	 * @depends testParseCommandArgument
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Too many arguments, expected arguments: "kernel" "command".
	 */
	public function testParseTooManyArguments()
	{
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		$this->writeAttribute(self::$input, 'parsed', array());
		
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		
		$this->callMethod(self::$input, 'parseArgument', 'toomany');
	}
	
	/**
	 * @covers ::parseArgument
	 * @depends testParseTooManyArguments
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage No arguments expected, got "debug:config".
	 */
	public function testParseNotExpectedArgument()
	{
		$this->writeAttribute(self::$input, 'definition', new InputDefinition());
		
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
	}
	
	/**
	 * @covers ::parseArgument()
	 * @depends testParseNotExpectedArgument
	 */
	public function testParseArrayArgument()
	{
		$definition = $this->getDefaultInputDefinition();
		$definition->addArgument(new InputArgument('files', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Files to process'));
		$this->writeAttribute(self::$input, 'definition', $definition);

		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		$this->writeAttribute(self::$input, 'parsed', array());
		
		$this->callMethod(self::$input, 'parseArgument', 'debug:config');
		
		$this->callMethod(self::$input, 'parseArgument', 'autoexec.bat');
		$this->callMethod(self::$input, 'parseArgument', 'config.sys');
		
		$this->assertEquals(array(
			'kernel' => null,
			'command' => 'debug:config',
			'files' => array(
				'autoexec.bat',
				'config.sys',
			)
		), $this->readAttribute(self::$input, 'arguments'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 */
	public function testParseLongOptionWithValue()
	{
		$this->callMethod(self::$input, 'parseLongOption', '--env=dev');
		
		$this->assertEquals(array(
			'env' => 'dev',
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @depends testParseLongOptionWithValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--ansi" option does not accept a value.
	 */
	public function testParseLongOptionWithNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'parseLongOption', '--ansi=color');
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @depends testParseLongOptionWithNotAcceptedValue
	 */
	public function testParseLongOptionWithoutValue()
	{
		$this->callMethod(self::$input, 'parseLongOption', '--ansi');
		
		$this->assertEquals(array(
			'ansi' => true,
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption()
	 * @depends testParseLongOptionWithoutValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 */
	public function testParseLongOptionWithoutRequiredValue()
	{
		$this->callMethod(self::$input, 'parseLongOption', '--env');
	}
	
	/**
	 * @covers ::parseLongOption
	 * @depends testParseLongOptionWithoutRequiredValue
	 */
	public function testParseLongArrayOption()
	{
		$this->callMethod(self::$input, 'parseLongOption', '--file=autoexec.bat');
		$this->callMethod(self::$input, 'parseLongOption', '--file=config.sys');
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
				'config.sys',
			)
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption
	 * @depends testParseLongArrayOption
	 */
	public function testParseLongArrayOptionParseNextValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array('autoexec.bat'));
		
		$this->callMethod(self::$input, 'parseLongOption', '--file');
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
			)
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption
	 * @depends testParseLongArrayOptionParseNextValue
	 */
	public function testParseLongArrayOptionParseNoNullValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array());
		
		$this->callMethod(self::$input, 'parseLongOption', '--file=');
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseLongOption
	 * @depends testParseLongArrayOptionParseNoNullValue
	 */
	public function testParseLongArrayOptionParseNoOption()
	{
		$this->writeAttribute(self::$input, 'parsed', array('-edev'));
		
		$this->callMethod(self::$input, 'parseLongOption', '--file', null);
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 */
	public function testParseShortOptionWithValue()
	{
		$this->callMethod(self::$input, 'parseShortOption', '-e=dev');
		
		$this->assertEquals(array(
			'env' => 'dev',
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortOptionWithValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-1" option does not exist.
	 */
	public function testParseShortOptionWithNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'parseShortOption', '-n1');
	}

	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortOptionWithValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-n" option accepts no value.
	 */
	public function testParseShortOptionWithEqualSignAndNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'parseShortOption', '-n=1');
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortOptionWithNotAcceptedValue
	 */
	public function testParseShortOptionWithoutValue()
	{
		$this->callMethod(self::$input, 'parseShortOption', '-n');
		
		$this->assertEquals(array(
			'no-interaction' => true,
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortOptionWithoutValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "--env" option requires a value.
	 */
	public function testParseShortOptionWithoutRequiredValue()
	{
		$this->callMethod(self::$input, 'parseShortOption', '-e');
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortOptionWithoutRequiredValue
	 */
	public function testParseShortArrayOption()
	{
		$this->callMethod(self::$input, 'parseShortOption', '-f=autoexec.bat');
		$this->callMethod(self::$input, 'parseShortOption', '-f=config.sys');
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
				'config.sys',
			)
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortArrayOption
	 */
	public function testParseShortArrayOptionParseNextValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array('autoexec.bat'));
		
		$this->callMethod(self::$input, 'parseShortOption', '-f');
		
		$this->assertEquals(array(
			'file' => array(
				'autoexec.bat',
			)
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortArrayOptionParseNextValue
	 */
	public function testParseShortArrayOptionParseNoNullValue()
	{
		$this->writeAttribute(self::$input, 'parsed', array());
		
		$this->callMethod(self::$input, 'parseShortOption', '-f=');
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOption()
	 * @depends testParseShortArrayOptionParseNoNullValue
	 */
	public function testParseShortArrayOptionParseNoOption()
	{
		$this->writeAttribute(self::$input, 'parsed', array('-edev'));
		
		$this->callMethod(self::$input, 'parseShortOption', '-f', null);
		
		$this->assertEquals(array(), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 */
	public function testParseShortOptionSetWithValue()
	{
		$this->callMethod(self::$input, 'parseShortOptionSet', 'nedev');
		
		$this->assertEquals(array(
			'no-interaction' => true,
			'env' => 'dev',
		), $this->readAttribute(self::$input, 'options'));
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @depends testParseShortOptionSetWithValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-1" option does not exist.
	 */
	public function testParseShortOptionSetWithNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'parseShortOptionSet', 'n1');
	}
	
	/**
	 * @covers ::parseShortOptionSet()
	 * @depends testParseShortOptionSetWithNotAcceptedValue
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage The "-n" option accepts no value.
	 */
	public function testParseShortOptionSetWithEqualSignAndNotAcceptedValue()
	{
		$this->callMethod(self::$input, 'parseShortOptionSet', 'n=1');
	}
	
	/**
	 * @covers ::parse()
	 * @depends testAddLongArrayOptionParseNoOption
	 * @depends testAddShortArrayOptionParseNoOption
	 * @depends testParseNotExpectedArgument
	 * @depends testParseLongArrayOptionParseNoOption
	 * @depends testParseShortArrayOptionParseNoOption
	 * @depends testParseShortOptionSetWithEqualSignAndNotAcceptedValue
	 */
	public function testParse()
	{
		$definition = $this->getDefaultInputDefinition();
		$definition->addArgument(new InputArgument('files', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Files to process'));
		
		self::$input->bind($definition);
		
		$this->assertEquals(array(
			'kernel' => 'boot',
			'command' => 'debug:config',
			'files' => array(
				''
			),
		), self::$input->getArguments());
		
		$this->assertEquals(array(
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
			'file' => array(
				'dev',
			)
		), self::$input->getOptions());
	}
	
	/**
	 * @covers ::parse()
	 * @depends testParse
	 */
	public function testParseEmptyArgument()
	{
		$this->setUp(array('bin/console', '-edev', '', '--'));
		
		self::$input->bind($this->getDefaultInputDefinition());
		
		$this->assertEquals(array(
			'kernel' => null,
			'command' => '',
		), self::$input->getArguments());
		
		$this->assertEquals(array(
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
			'file' => array()
		), self::$input->getOptions());
	}
	
	/**
	 * @covers ::__toString()
	 */
	public function testToString()
	{
		$this->assertEquals('-edev -f=dev -l - -q --no-debug -- boot \'debug:config\' ',(string) self::$input);
	}
}
