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

use Motana\Bundle\MultiKernelBundle\Console\Descriptor\MarkdownDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Output\BufferedOutput;
use Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Descriptor\MarkdownDescriptor
 */
class MarkdownDescriptorTest extends ApplicationTestCase
{
	/**
	 * @var MarkdownDescriptor
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
		
		self::$descriptor = new MarkdownDescriptor();
		
		self::$output = new BufferedOutput();
		
		if (null !== $type) {
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
			self::$command->setDefinition(array(
				new InputArgument('method', InputArgument::OPTIONAL, $description = 'Test method'),
				new InputOption('--all', '-a', InputOption::VALUE_NONE, 'Run all tests'),
				new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'File to process'),
				new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			));
		}
	}
	
	/**
	 * @covers ::formatDescription()
	 */
	public function testFormatDescription()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->assertEquals($this->getTemplate('description'), $this->callMethod(self::$descriptor, 'formatDescription', self::$command->getDescription()));
		
		$help = $this->callMethod(self::$descriptor, 'getProcessedHelp', self::$command);
		
		$this->assertEquals($this->getTemplate('help'), $this->callMethod(self::$descriptor, 'formatDescription', $help));
	}
	
	/**
	 * @covers ::describeInputArgument()
	 * @depends testFormatDescription
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
	 * @depends testFormatDescription
	 */
	public function testDescribeInputOption()
	{
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('all'));
		
		$this->assertEquals($this->getTemplate('option'), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputOption()
	 * @depends testDescribeInputOption
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
	 * - description
	 * - help
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
	 * @param string $format Output format (default: md)
	 * @return string
	 */
	protected static function getTemplate($case, array $options = array(), $format = 'md')
	{
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		switch ($case) {
			case 'description':
				return 'JsonDescriptor test command';
			case 'help':
				return <<<EOD
Tests the `MarkDownDescriptor`.

Usage:
test
bin/console test
EOD;
		}
		
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
