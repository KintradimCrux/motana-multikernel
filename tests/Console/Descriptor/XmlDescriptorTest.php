<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Console\Descriptor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Motana\Bundle\MultikernelBundle\Console\Descriptor\XmlDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Output\BufferedOutput;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Descriptor\XmlDescriptor
 */
class XmlDescriptorTest extends ApplicationTestCase
{
	/**
	 * @var XmlDescriptor
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
	 * @see \Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase::setUp()
	 */
	protected function setUp($type = null, $app = null, $environment = 'test', $debug = false)
	{
		if (null !== $type) {
			parent::setUp($type, $app, $environment, $debug);
		} else {
			self::$application = null;
		}
		
		self::$descriptor = new XmlDescriptor();
		
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
	 * @covers ::writeDocument()
	 */
	public function testWriteDocument()
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->appendChild($child1 = $dom->createElement('child'));
		$child1->setAttribute('id', 'child1');
		
		$child1->appendChild($child2 = $dom->createElement('grandchild'));
		$child2->setAttribute('id', 'child2');
		
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('document'), self::$output->fetch());
	}
	
	/**
	 * @covers ::appendDocument()
	 * @depends testWriteDocument
	 */
	public function testAppendDocument()
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->appendChild($child1 = $dom->createElement('child'));
		$child1->setAttribute('id', 'child1');
		
		$dom2 = new \DOMDocument('1.0', 'UTF-8');
		$dom2->appendChild($child2 = $dom2->createElement('grandchild'));
		$child2->setAttribute('id', 'child2');
		
		$this->callMethod(self::$descriptor, 'appendDocument', $child1, $dom2);
		
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('document'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputArgumentDocument()
	 * @depends testWriteDocument
	 */
	public function testGetInputArgumentDocument()
	{
		$dom = $this->callMethod(self::$descriptor, 'getInputArgumentDocument', self::$command->getDefinition()->getArgument(0));
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('argument'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputArgumentDocument()
	 * @depends testWriteDocument
	 */
	public function testGetInputArgumentDocumentWithDefault()
	{
		self::$command->getDefinition()->getArgument(0)->setDefault('standard');
		
		$dom = $this->callMethod(self::$descriptor, 'getInputArgumentDocument', self::$command->getDefinition()->getArgument(0));
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('argument_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputOptionDocument()
	 * @depends testWriteDocument
	 */
	public function testGetInputOptionDocument()
	{
		$dom = $this->callMethod(self::$descriptor, 'getInputOptionDocument', self::$command->getDefinition()->getOption('all'));
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('option'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputOptionDocument()
	 * @depends testWriteDocument
	 */
	public function testGetInputOptionDocumentWithDefault()
	{
		self::$command->getDefinition()->getOption('file')->setDefault('autoexec.bat');
		
		$dom = $this->callMethod(self::$descriptor, 'getInputOptionDocument', self::$command->getDefinition()->getOption('file'));
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('option_with_default'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputOptionDocument()
	 * @depends testWriteDocument
	 */
	public function testGetInputOptionDocumentWithShortcuts()
	{
		$dom = $this->callMethod(self::$descriptor, 'getInputOptionDocument', self::$command->getDefinition()->getOption('verbose'));
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('option_with_shortcut'), self::$output->fetch());
	}
	
	/**
	 * @covers ::getInputDefinitionDocument()
	 * @depends testGetInputArgumentDocument
	 * @depends testGetInputOptionDocument
	 */
	public function testGetInputDefinitionDocument()
	{
		$dom = $this->callMethod(self::$descriptor, 'getInputDefinitionDocument', self::$command->getDefinition());
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('definition'), self::$output->fetch());
	}
	
	/**
	 * Data provider for testGetCommandData().
	 *
	 * @return array
	 */
	public function provide_testGetCommandDocument_data()
	{
		return array(
			array('working', null, 'command_multikernel', array()),
			array('working', 'app', 'command_appkernel', array()),
		);
	}
	
	/**
	 * @covers ::getCommandDocument()
	 * @dataProvider provide_testGetCommandDocument_data
	 * @depends testGetInputDefinitionDocument
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $options Display options
	 */
	public function testGetCommandDocument($type, $app, $template, array $options = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		$dom = $this->callMethod(self::$descriptor, 'getCommandDocument', self::$command);
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}
	
	/**
	 * Data provider for testDescribeApplication()
	 * @return array
	 */
	public function provide_testGetApplicationDocument_data()
	{
		return array(
			array('working', null, 'application_multikernel', array()),
			array('working', null, 'application_multikernel', array('namespace' => 'debug')),
			array('working', 'app', 'application_appkernel', array()),
			array('working', 'app', 'application_appkernel', array('namespace' => 'debug')),
		);
	}
	
	/**
	 * @covers ::getApplicationDocument()
	 * @dataProvider provide_testGetApplicationDocument_data
	 * @depends testGetCommandDocument
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template file name
	 * @param array $options Display options
	 */
	public function testGetApplicationDocument($type, $app, $template, $options)
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		$dom = $this->callMethod(self::$descriptor, 'getApplicationDocument', self::$application, isset($options['namespace']) ? $options['namespace'] : null);
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate($template, $options), self::$output->fetch());
	}
	
	/**
	 * @covers ::getApplicationDocument()
	 * @depends testGetApplicationDocument
	 */
	public function testGetApplicationDocumentRemovesAliasesAndEmptyNamespaces()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working', 'app');
		
		self::$application->add(self::$application->get('help')->setAliases(array('help:help')));
		
		$dom = $this->callMethod(self::$descriptor, 'getApplicationDocument', self::$application, isset($options['namespace']) ? $options['namespace'] : null);
		$this->callMethod(self::$descriptor, 'writeDocument', $dom);
		
		$this->assertEquals($this->getTemplate('application_appkernel', array('alias' => 'help')), self::$output->fetch());
	}
	
	/**
	 * @covers ::describeInputArgument()
	 * @depends testGetInputArgumentDocument
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
	 * @depends testGetInputOptionDocument
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
	 * @covers ::describeInputOption()
	 * @depends testDescribeInputOption
	 */
	public function testDescribeInputOptionWithShortcut()
	{
		
		self::$descriptor->describe(self::$output, self::$command->getDefinition()->getOption('verbose'));
		
		$this->assertEquals($this->getTemplate('option_with_shortcut'), self::$output->fetch());
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
	 * @depends testGetApplicationDocument
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
	 * @covers ::describeApplication()
	 * @depends testDescribeApplication
	 */
	public function testDescribeApplicationRemovesAliasesAndEmptyNamespaces()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working', 'app');
		
		self::$application->add(self::$application->get('help')->setAliases(array('help:help')));
		
		self::$descriptor->describe(self::$output, self::$application, array());
		
		$this->assertEquals($this->getTemplate('application_appkernel', array('alias' => 'help')), self::$output->fetch());
	}
	
	/**
	 * @covers ::describe()
	 * @ depends testDescribeApplication
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
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format (default: txt)
	 * @return string
	 */
	protected static function getTemplate($case, array $options = array(), $format = 'xml')
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
