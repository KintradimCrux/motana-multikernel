<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Console\Helper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

use Motana\Bundle\MultiKernelBundle\Console\Helper\DescriptorHelper;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\JsonDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\MarkdownDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\TextDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\XmlDescriptor;
use Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Helper\DescriptorHelper
 */
class DescriptorHelperTest extends ApplicationTestCase
{
	/**
	 * @var DescriptorHelper
	 */
	protected static $helper;
	
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
		
		self::$helper = new DescriptorHelper();
		
		self::$output = new BufferedOutput();
		
		if (null !== $type) {
			self::$command = self::$application->find('help');
		} else {
			self::$command = new Command('test');
			self::$command->setDescription('DescriptorHelper test command');
			self::$command->setHelp(<<<EOH
Tests the DescriptorHelper.
					
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
	 * @covers ::__construct()
	 * @covers ::register()
	 */
	public function testConstructor()
	{
		$descriptors = $this->readAttribute(self::$helper, 'descriptors');
		
		$this->assertEquals(4, count($descriptors));
		
		$this->assertEquals(array('txt', 'xml', 'json', 'md'), array_keys($descriptors));
		
		$this->assertInstanceOf(TextDescriptor::class, $descriptors['txt']);
		$this->assertInstanceOf(XmlDescriptor::class, $descriptors['xml']);
		$this->assertInstanceOf(JsonDescriptor::class, $descriptors['json']);
		$this->assertInstanceOf(MarkdownDescriptor::class, $descriptors['md']);
	}
	
	/**
	 * @covers ::getName()
	 */
	public function testGetName()
	{
		$this->assertEquals('descriptor', self::$helper->getName());
	}
	
	/**
	 * Object provider for testDescribe().
	 * 
	 * @param string $template Template name
	 * @return mixed
	 */
	public function provide_testDescribe_object($template)
	{
		switch ($template) {
			case 'argument':
				return self::$command->getDefinition()->getArgument(0);
			case 'argument_with_default':
				$object = self::$command->getDefinition()->getArgument(0);
				$object->setDefault('standard');
				return $object;
			case 'option':
				return self::$command->getDefinition()->getOption('all');
				break;
			case 'option_with_default':
				$object = self::$command->getDefinition()->getOption('file');
				$object->setDefault('autoexec.bat');
				return $object;
			case 'definition':
				return self::$command->getDefinition();
			case 'command_multikernel':
			case 'command_appkernel':
				return self::$command;
			case 'application_multikernel':
			case 'application_appkernel':
				return self::$application;
		}
	}
	
	/**
	 * Data provider for testDescribe().
	 *
	 * @return array
	 */
	public function provide_testDescribe_data()
	{
		return array(
			array(null, null, 'argument', 'json', array()),
			array(null, null, 'argument', 'md', array()),
			array(null, null, 'argument', 'txt', array()),
			array(null, null, 'argument', 'xml', array()),
			array(null, null, 'argument_with_default', 'json', array()),
			array(null, null, 'argument_with_default', 'md', array()),
			array(null, null, 'argument_with_default', 'txt', array()),
			array(null, null, 'argument_with_default', 'xml', array()),
			array(null, null, 'option', 'json', array()),
			array(null, null, 'option', 'md', array()),
			array(null, null, 'option', 'txt', array()),
			array(null, null, 'option', 'xml', array()),
			array(null, null, 'option_with_default', 'json', array()),
			array(null, null, 'option_with_default', 'md', array()),
			array(null, null, 'option_with_default', 'txt', array()),
			array(null, null, 'option_with_default', 'xml', array()),
			array(null, null, 'definition', 'json', array()),
			array(null, null, 'definition', 'md', array()),
			array(null, null, 'definition', 'txt', array()),
			array(null, null, 'definition', 'xml', array()),
			array('working', null, 'command_multikernel', 'json', array()),
			array('working', null, 'command_multikernel', 'md', array()),
			array('working', null, 'command_multikernel', 'txt', array()),
			array('working', null, 'command_multikernel', 'xml', array()),
			array('working', 'app', 'command_appkernel', 'json', array()),
			array('working', 'app', 'command_appkernel', 'md', array()),
			array('working', 'app', 'command_appkernel', 'txt', array()),
			array('working', 'app', 'command_appkernel', 'xml', array()),
			array('working', null, 'application_multikernel', 'json', array()),
			array('working', null, 'application_multikernel', 'json', array('namespace' => 'debug')),
			array('working', null, 'application_multikernel', 'md', array()),
			array('working', null, 'application_multikernel', 'md', array('namespace' => 'debug')),
			array('working', null, 'application_multikernel', 'txt', array()),
			array('working', null, 'application_multikernel', 'txt', array('namespace' => 'debug')),
			array('working', null, 'application_multikernel', 'txt', array('raw_text' => true)),
			array('working', null, 'application_multikernel', 'txt', array('namespace' => 'debug', 'raw_text' => true)),
			array('working', null, 'application_multikernel', 'xml', array()),
			array('working', null, 'application_multikernel', 'xml', array('namespace' => 'debug')),
			array('working', 'app', 'application_appkernel', 'json', array()),
			array('working', 'app', 'application_appkernel', 'json', array('namespace' => 'debug')),
			array('working', 'app', 'application_appkernel', 'md', array()),
			array('working', 'app', 'application_appkernel', 'md', array('namespace' => 'debug')),
			array('working', 'app', 'application_appkernel', 'txt', array()),
			array('working', 'app', 'application_appkernel', 'txt', array('namespace' => 'debug')),
			array('working', 'app', 'application_appkernel', 'txt', array('raw_text' => true)),
			array('working', 'app', 'application_appkernel', 'txt', array('namespace' => 'debug', 'raw_text' => true)),
			array('working', 'app', 'application_appkernel', 'xml', array()),
			array('working', 'app', 'application_appkernel', 'xml', array('namespace' => 'debug')),
		);
	}
	
	/**
	 * @covers ::describe()
	 * @dataProvider provide_testDescribe_data
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param string $format Output format
	 * @param array $options Display options
	 */
	public function testDescribe($type, $app, $template, $format, array $options = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		$object = $this->provide_testDescribe_object($template);
		
		self::$helper->describe(self::$output, $object, array_merge($options, array('format' => $format)));
		
		$this->assertEquals($this->getTemplate($template, $options, $format), self::$output->fetch());
	}
	
	/**
	 * @covers ::describe()
	 * @depends testDescribe
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Unsupported format "invalid".
	 */
	public function testDescribeThrowsException()
	{
		self::$helper->describe(self::$output, null, array('format' => 'invalid'));
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
	 * @param string $format Output format
	 * @return string
	 */
	protected static function getTemplate($case, array $options = array(), $format)
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
