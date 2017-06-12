<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Test;

use Tests\Motana\Bundle\MultiKernelBundle\Command\HelpCommandTest;

use Motana\Bundle\MultiKernelBundle\Console\Application;
use Motana\Bundle\MultiKernelBundle\Console\MultiKernelApplication;
use Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase;
use Motana\Bundle\MultiKernelBundle\Test\CommandTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Test\CommandTestCase
 */
class CommandTestCaseTest extends ApplicationTestCase
{
	/**
	 * @var HelpCommandTest
	 */
	protected static $testCase;
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase::setUp()
	 */
	protected function setUp($type = null, $app = null, $environment = 'test', $debug = false)
	{
		if (null !== $type) {
			parent::setUp($type, $app, $environment, $debug);
		}
		
		self::$testCase = new HelpCommandTest();
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertEquals('help', $this->readAttribute(self::$testCase, 'commandName'));
		$this->assertEquals(array('command_name' => 'help'), $this->readAttribute(self::$testCase, 'commandParameters'));
	}
	
	/**
	 * @covers ::setUp()
	 */
	public function testSetUpBootKernel()
	{
		$this->callMethod(self::$testCase, 'setUp', 'working');
		
		$this->assertInstanceOf(MultiKernelApplication::class, $this->readAttribute(ApplicationTestCase::class, 'application'));
	}
	
	/**
	 * @covers ::setUp()
	 * @depends testSetUpBootKernel
	 */
	public function testSetUpAppKernel()
	{
		$this->callMethod(self::$testCase, 'setUp', 'working', 'app');
		
		$this->assertInstanceOf(Application::class, $this->readAttribute(ApplicationTestCase::class, 'application'));
	}
	
	/**
	 * Data provider for testTestExecute().
	 * 
	 * @return array
	 */
	public function provide_testTestExecute_data()
	{
		$this->setUp();
		
		return self::$testCase->provide_testExecute_data();
	}
	
	/**
	 * @covers ::testExecute()
	 * @dataProvider provide_testTestExecute_data
	 */
	public function testTestExecute($type, $app, $template, array $parameters = array())
	{
		self::$testCase->testExecute($type, $app, $template, $parameters);
	}
	
	/**
	 * @covers ::convertParametersToOptions()
	 */
	public function testConvertParametersToOptions()
	{
		$this->assertEquals(array(), $this->callStaticMethod(CommandTestCase::class, 'convertParametersToOptions'));

		$this->assertEquals(array(
			'command' => 'help',
			'command_name' => 'help',
			'--format' => 'txt',
			'--raw' => true,
		), $this->callStaticMethod(CommandTestCase::class, 'convertParametersToOptions', array(
			'command' => 'help',
			'command_name' => 'help',
			'--format' => 'txt',
			'--raw' => true,
		)));
	}
	
	/**
	 * Data provider for testGetTemplate().
	 * 
	 * @return array
	 */
	public function provide_testGetTemplate_data()
	{
		$this->setUp();
		
		$data = array();
		$executeData = self::$testCase->provide_testExecute_data();
		
		foreach ($executeData as $key => $row) {
			$options = $this->callStaticMethod(HelpCommandTest::class, 'convertParametersToOptions', $row[3]);
			
			$data[$key] = array($row[2], $options, $row[3]['--format'], 'help');
		}
		
		return $data;
	}
	
	/**
	 * @covers ::getTemplate()
	 * @dataProvider provide_testGetTemplate_data
	 */
	public function testGetTemplate($case, array $options = array(), $format, $commandName)
	{
		$this->assertEquals($this->getTemplate($case, $options, $format, $commandName), 
			$this->callStaticMethod(CommandTestCase::class, 'getTemplate', $case, $options, $format, $commandName)
		);
	}
	
	/**
	 * @covers ::getTemplate()
	 */
	public function testGetTemplateSavesNewTemplate()
	{
		$output = $this->getStaticAttribute(CommandTestCase::class, 'output');
		
		$output->write('some content');
		
		$case = 'command_multikernel';
		$options = array();
		$format = 'txt';
		$commandName = 'invalid';
		
		$template = $this->callStaticMethod(CommandTestCase::class, 'getTemplate', $case, $options, $format, $commandName);
		
		$this->assertEquals($this->getTemplate($case, $options, $format, $commandName), $template);
		
		$this->assertEquals('some content', file_get_contents($file = self::$fixturesDir . '/output/commands/invalid/' . $format . '/' . $case . '.' . $format));
		
		self::getFs()->remove(dirname(dirname($file)));
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Case:
	 * - command_multikernel
	 * - command_appkernel
	 * @param array $options Display options
	 * @param string $format Output format
	 * @param string $commandName Command name
	 * @return string
	 */
	protected static function getTemplate($case, array $options = array(), $format, $commandName)
	{
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		if (is_file($file = self::$fixturesDir . '/output/commands/' . $commandName . '/' . $format . '/' . $case . '.' . $format)) {
			return file_get_contents($file);
		}
		
		self::getFs()->mkdir(dirname($file));
		
		$output = clone(self::readAttribute(CommandTestCase::class, 'output'));
		$content = $output->fetch();
		file_put_contents($file, $content);
		
		return $content;
	}
}
