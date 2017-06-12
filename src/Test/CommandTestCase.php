<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Test;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Abstract base class for testing console commands.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class CommandTestCase extends ApplicationTestCase
{
	/**
	 * @var string
	 */
	protected $commandName;
	
	/**
	 * @var array
	 */
	protected $commandParameters = array();
	
	/**
	 * @var Command
	 */
	protected static $command;
	
	/**
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * Constructor.
	 *
	 * @param string $name Test name
	 * @param array $data Test dataset
	 * @param string $dataName Test dataset name
	 * @param string $commandName Name of the command to test
	 * @param array $parameters Command parameters
	 */
	public function __construct($name = null, array $data = [], $dataName = '', $commandName = null, array $parameters=array())
	{
		parent::__construct($name, $data, $dataName);
		
		$this->commandName = $commandName;
		$this->commandParameters = $parameters;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase::setUp()
	 */
	protected function setUp($type = null, $app = null, $environment = 'test', $debug = false)
	{
		if (null !== $type) {
			parent::setUp($type, $app, $environment, $debug);
			
			self::$command = self::$application->get($this->commandName);
			self::$command->mergeApplicationDefinition(true);
			
			self::$output = new BufferedOutput();
		}
	}
	
	/**
	 * Data provider for testExecute().
	 *
	 * @return array
	 */
	abstract public function provide_testExecute_data();
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_testExecute_data
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 */
	public function testExecute($type, $app, $template, array $parameters = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);
		
		$input = new ArrayInput(array_merge(array('command' => $this->commandName), $this->commandParameters, $parameters));
		$input->bind(self::$command->getDefinition());
		
		$this->callMethod(self::$command, 'execute', $input, self::$output);
		
		$options = $this->convertParametersToOptions($parameters);
		
		$this->assertEquals($this->getTemplate($template, $options, $parameters['--format'], $this->commandName), self::$output->fetch());
	}
	
	/**
	 * Convert input parameters to options for the command.
	 * 
	 * @param array $parameters Parameters to convert
	 * @return array
	 */
	protected static function convertParametersToOptions(array $parameters = array())
	{
		return $parameters;
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
		
		$output = clone(self::$output);
		$content = $output->fetch();
		file_put_contents($file, $content);
		
		return $content;
	}
}
