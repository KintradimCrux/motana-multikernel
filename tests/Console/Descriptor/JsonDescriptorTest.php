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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

use Motana\Bundle\MultiKernelBundle\Console\Descriptor\JsonDescriptor;
use Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Descriptor\JsonDescriptor
 */
class JsonDescriptorTest extends ApplicationTestCase
{
	/**
	 * @var JsonDescriptor
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
		
		self::$descriptor = new JsonDescriptor();
		
		self::$output = new BufferedOutput();
		
		$this->writeAttribute(self::$descriptor, 'output', self::$output);
		
		if (null !== $type) {
			self::$command = self::$application->find('help');
		} else {
			self::$command = new Command('test');
			self::$command->setDescription('JsonDescriptor test command');
			self::$command->setHelp(<<<EOH
Tests the JsonDescriptor.

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
	 * @covers ::writeData()
	 */
	public function testWriteData()
	{
		$this->callMethod(self::$descriptor, 'writeData', array(
			'name' => 'test',
			'usage' => 'test [options] [arguments]',
			'description' => 'JsonDescriptor test command',
			'help' => 'Tests the writeData() method',
			'definition' => array(
				'arguments' => array(),
				'options' => array(),
			)
		));
		
		$this->assertEquals($this->getTemplate('data'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputArgumentData()
	 * @depends testWriteData
	 */
	public function testGetInputArgumentData()
	{
		$this->callmethod(self::$descriptor, 'writeData', 
			$this->callMethod(self::$descriptor, 'getInputArgumentData', self::$command->getDefinition()->getArgument(0))
		);
		
		$this->assertEquals($this->getTemplate('argument'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputArgumentData()
	 * @depends testGetInputArgumentData
	 */
	public function testGetInputArgumentDataWithDefault()
	{
		self::$command->getDefinition()->getArgument(0)->setDefault('standard');
		
		$this->callmethod(self::$descriptor, 'writeData',
			$this->callMethod(self::$descriptor, 'getInputArgumentData', self::$command->getDefinition()->getArgument(0))
		);
		
		$this->assertEquals($this->getTemplate('argument_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputOptionData()
	 * @depends testWriteData
	 */
	public function testGetInputOptionData()
	{
		$this->callmethod(self::$descriptor, 'writeData',
			$this->callMethod(self::$descriptor, 'getInputOptionData', self::$command->getDefinition()->getOption('all'))
		);
		
		$this->assertEquals($this->getTemplate('option'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputOptionData()
	 * @depends testGetInputOptionData
	 */
	public function testGetInputOptionDataWithDefault()
	{
		self::$command->getDefinition()->getOption('file')->setDefault('autoexec.bat');
		
		$this->callmethod(self::$descriptor, 'writeData',
			$this->callMethod(self::$descriptor, 'getInputOptionData', self::$command->getDefinition()->getOption('file'))
		);
		
		$this->assertEquals($this->getTemplate('option_with_default'), self::$output->fetch());
	}
	
	
	/**
	 * @covers ::getInputDefinitionData()
	 * @depends testGetInputArgumentData
	 * @depends testGetInputOptionData
	 */
	public function testGetInputDefinitionData()
	{
		$this->callMethod(self::$descriptor, 'writeData',
			$this->callMethod(self::$descriptor, 'getInputDefinitionData', self::$command->getNativeDefinition())
		);
		
		$this->assertEquals($this->getTemplate('definition'), self::$output->fetch());
	}

	/**
	 * Data provider for testGetCommandData().
	 * 
	 * @return array
	 */
	public function provide_testGetCommandData_data()
	{
		return array(
			array('working', null, 'command_multikernel', array()),
			array('working', 'app', 'command_appkernel', array()),
		);
	}
	
	/**
	 * @covers ::getCommandData()
	 * @dataProvider provide_testGetCommandData_data
	 * @depends testGetInputDefinitionData
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $options Display options
	 */
	public function testGetCommandData($type, $app, $template, array $options = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		$this->callMethod(self::$descriptor, 'writeData',
			$this->callMethod(self::$descriptor, 'getCommandData', self::$command)
		);
		
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputArgument()
	 * @depends testWriteData
	 * @depends testGetInputArgumentData
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
	public function testDescribeInputArgumentWithDefault()
	{
		self::$command->getDefinition()->getArgument(0)->setDefault('standard');
		
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getArgument(0));
		
		$this->assertEquals($this->getTemplate('argument_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputOption()
	 * @depends testWriteData
	 * @depends testGetInputOptionData
	 */
	public function testDescribeInputOption()
	{
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('all'));
		
		$this->assertEquals($this->getTemplate('option'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputOption()
	 * @depends testWriteData
	 * @depends testGetInputOptionData
	 */
	public function testDescribeInputOptionWithDefault()
	{
		self::$command->getDefinition()->getOption('file')->setDefault('autoexec.bat');
		
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('file'));
		
		$this->assertEquals($this->getTemplate('option_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputDefinition()
	 * @depends testDescribeInputArgument
	 * @depends testDescribeInputOption
	 */
	public function testDescribeInputDefinition()
	{
		self::$descriptor->describe(self::$output, self::$command->getNativeDefinition());
		
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
			array('working', 'app', 'application_appkernel', array()),
			array('working', 'app', 'application_appkernel', array('namespace' => 'debug')),
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
	 * - data
	 * - argument
	 * - argument_with_default
	 * - option
	 * - option_with_default
	 * - definition
	 * - command_multikernel
	 * - command_appkernel
	 * - application_appkernel
	 * - application_multikernel
	 * @param array $options Display options
	 * @param string $format Output format (default: json)
	 * @return string
	 */
	protected static function getTemplate($case, array $options = array(), $format = 'json')
	{
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		if (is_file($file = self::$fixturesDir . '/output/descriptor/' . $format . '/'. $case . '.' . $format)) {
			return file_get_contents($file);
		}
		
		self::getFs()->mkdir(dirname($file));
		
		$output = clone(self::$output);
		$content = $output->fetch();
		file_put_contents($file, $content);
		
		return $content;
	}
}
