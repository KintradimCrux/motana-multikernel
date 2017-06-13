<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Console\Descriptor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Motana\Bundle\MultiKernelBundle\Console\Descriptor\TextDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Output\BufferedOutput;
use Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Descriptor\TextDescriptor
 */
class TextDescriptorTest extends ApplicationTestCase
{
	/**
	 * @var TextDescriptor
	 */
	protected static $descriptor;
	
	/**
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * @var Command
	 */
	protected static $command;
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase::setUp()
	 */
	protected function setUp($type = null, $app = null, $environment = 'test', $debug = false)
	{
		if (null !== $type) {
			parent::setUp($type, $app, $environment, $debug);
		} else {
			self::$application = null;
		}
		
		self::$descriptor = new TextDescriptor();
		
		self::$output = new BufferedOutput();
		
		$this->writeAttribute(self::$descriptor, 'output', self::$output);
		
		if (null !== $type) {
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
			self::$command->setDefinition(array(
				new InputArgument('method', InputArgument::OPTIONAL, $description = 'Test method'),
				new InputOption('--all', '-a', InputOption::VALUE_NONE, 'Run all tests'),
				new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'File to process'),
				new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			));
		}
	}
	
	/**
	 * @covers ::writeText()
	 */
	public function testWriteText()
	{
		$this->callMethod(self::$descriptor, 'writeText', '<info>test</info>', array('raw_output' => true));
		
		$this->assertEquals('<info>test</info>', self::$output->fetch());
		
		$this->callMethod(self::$descriptor, 'writeText', '<info>test</info>', array('raw_text' => true, 'raw_output' => true));
		
		$this->assertEquals('test', self::$output->fetch());
	}
	
	/**
	 * @covers ::getCommandAliasesText()
	 */
	public function testGetCommandAliasesText()
	{
		self::$command->setAliases(array('dummy:test','alias:test'));
		
		$this->assertEquals('[dummy:test|alias:test] ', $this->callMethod(self::$descriptor, 'getCommandAliasesText', self::$command));
	}
	
	/**
	 * Data provider for testFormatDefaultValue().
	 * 
	 * @return array
	 */
	public function provide_testFormatDefaultValue_data()
	{
		return array(
			array('null', null),
			array('1', 1),
			array('false', false),
			array('"test"', 'test'),
			array('"Test string that ends with backslash<<"', "Test string that ends with backslash\\"),
			array('[1,2,3]', array(1, 2, 3)),
			array('["one","two","three"]', array('one', 'two', 'three')),
			array('{}', new \stdClass()),
		);
	}
	
	/**
	 * @covers ::formatDefaultValue()
	 * @dataProvider provide_testFormatDefaultValue_data
	 */
	public function testFormatDefaultValue($expectedResult, $value)
	{
		$this->assertEquals($expectedResult, $this->callMethod(self::$descriptor, 'formatDefaultValue', $value));
	}
	
	/**
	 * @covers ::getColumnWidth()
	 */
	public function testGetColumnWidth()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working');
		
		$width = 0;
		$commands = self::$application->all();
		
		self::$application->get('help')->setAliases(array('help:help'));
		
		foreach ($commands as $command) {
			/** @var Command $command */
			$width = max($width, Helper::strlen($command->getName()));
			foreach ($command->getAliases() as $alias) {
				$width = max($width, Helper::strlen($alias));
			}
		}
		$width += 2;
		
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'getColumnWidth', $commands));
	}
	
	/**
	 * @covers ::calculateTotalWidthForArguments()
	 */
	public function testCalculateTotalWidthForArguments()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working');
		
		$width = 0;
		$arguments = self::$application->getDefinition()->getArguments();
		foreach ($arguments as $argument) {
			/** @var InputArgument $argument */
			$width = max($width, Helper::strlen($argument->getName()));
		}
		
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'calculateTotalWidthForArguments', $arguments));
	}
	
	/**
	 * @covers ::calculateTotalWidthForShortcuts()
	 */
	public function testCalculateTotalWidthForShortcuts()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working');
		
		$width = 0;
		$options = self::$application->getDefinition()->getOptions();
		foreach ($options as $option) {
			/** @var InputOption $option */
			$width = max($width, 1 + Helper::strlen($option->getShortcut()));
		}
		$width += 2;
		
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'calculateTotalWidthForShortcuts', $options));
	}
	
	/**
	 * @covers ::calculateTotalWidthForOptions()
	 * @depends testCalculateTotalWidthForShortcuts
	 */
	public function testCalculateTotalWidthForOptions()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working');
		
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
		
		$this->assertEquals($width, $this->callMethod(self::$descriptor, 'calculateTotalWidthForOptions', $options));
	}
	
	/**
	 * @covers ::describeInputArgument()
	 * @depends testFormatDefaultValue
	 */
	public function testDescribeInputArgument()
	{
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getArgument(0));
		
		$this->assertEquals($this->getTemplate('argument'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputArgument()
	 * @depends testDescribeInputArgument
	 */
	public function testDescribeInputArgumentWithDefaultValue()
	{
		self::$command->getDefinition()->getArgument(0)->setDefault('standard');
		
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getArgument(0));
		
		$this->assertEquals($this->getTemplate('argument_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputOption()
	 * @depends testFormatDefaultValue
	 * @depends testCalculateTotalWidthForShortcuts
	 * @depends testCalculateTotalWidthForOptions
	 */
	public function testDescribeInputOption()
	{
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('all'));
		
		$this->assertEquals($this->getTemplate('option'), self::$output->fetch());
	}
	
	public function testDescribeInputOptionWithDefaultValue()
	{
		self::$command->getDefinition()->getOption('file')->setDefault('autoexec.bat');
		
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('file'));
		
		$this->assertEquals($this->getTemplate('option_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputDefinition()
	 * @depends testFormatDefaultValue
	 * @depends testCalculateTotalWidthForArguments
	 * @depends testCalculateTotalWidthForShortcuts
	 * @depends testCalculateTotalWidthForOptions
	 * @depends testDescribeInputArgument
	 * @depends testDescribeInputOption
	 */
	public function testDescribeInputDefinition()
	{
		self::$descriptor->describe(self::$output, self::$command->getDefinition());
		
		$this->assertEquals($this->getTemplate('definition'), self::$output->fetch());
	}
	
	/**
	 * Data provider for testGetCommandData().
	 *
	 * @return array
	 */
	public function provide_testDescribeCommand_data()
	{
		return array(
			array('working', null, 'command_multikernel', array()),
			array('working', 'app', 'command_appkernel', array()),
		);
	}
	
	/**
	 * @covers ::describeCommand()
	 * @dataProvider provide_testDescribeCommand_data
	 * @depends testDescribeInputDefinition
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $options Display options
	 */
	public function testDescribeCommand($type, $app, $template, array $options = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		self::$descriptor->describe(self::$output, self::$command);
		
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}
	
	/**
	 * Data provider for testDescribeApplication()
	 * @return array
	 */
	public function provide_testDescribeApplication_data()
	{
		return array(
			array('working', null, 'application_multikernel', array()),
			array('working', null, 'application_multikernel', array('namespace' => 'debug')),
			array('working', null, 'application_multikernel', array('raw_text' => true)),
			array('working', null, 'application_multikernel', array('namespace' => 'debug', 'raw_text' => true)),
			array('working', 'app', 'application_appkernel', array()),
			array('working', 'app', 'application_appkernel', array('namespace' => 'debug')),
			array('working', 'app', 'application_appkernel', array('raw_text' => true)),
			array('working', 'app', 'application_appkernel', array('namespace' => 'debug', 'raw_text' => true)),
		);
	}
	
	/**
	 * @covers ::describeApplication()
	 * @dataProvider provide_testDescribeApplication_data
	 * @depends testDescribeCommand
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template file name
	 * @param array $options Display options
	 */
	public function testDescribeApplication($type, $app, $template, $options)
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		self::$descriptor->describe(self::$output, self::$application, $options);
		
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}
	
	/**
	 * @covers ::describe()
	 * @depends testDescribeApplication
	 * @expectedException Symfony\Component\Console\Exception\InvalidArgumentException
	 * @expectedExceptionMessage Object of type "stdClass" is not describable.
	 */
	public function testDescribeInvalidObject()
	{
		self::$descriptor->describe(self::$output, new \stdClass());
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Case:
	 * - argument
	 * - argument_with_default
	 * - option
	 * - option_with_default
	 * - definition
	 * - command_multikernel
	 * - command_appkernel
	 * - application_multikernel
	 * - application_appkernel
	 * @param array $options Display options
	 * @param string $format Output format (default: txt)
	 * @return string
	 */
	protected static function getTemplate($case, array $options = array(), $format = 'txt')
	{
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		if (is_file($file = self::$fixturesDir . '/output/descriptor/' . $format . '/' . $case . '.' . $format)) {
			return file_get_contents($file);
		}
		
		self::getFs()->mkdir(dirname($file));
		
		$output = clone(self::$output);
		$content = $output->fetch();
		file_put_contents($file, $content);
		
		return $content;
	}
}
