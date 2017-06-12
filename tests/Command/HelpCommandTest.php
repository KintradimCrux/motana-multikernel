<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Command;

use Motana\Bundle\MultiKernelBundle\Command\HelpCommand;
use Motana\Bundle\MultiKernelBundle\Test\CommandTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Command\HelpCommand
 */
class HelpCommandTest extends CommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'help', array('command_name' => 'help'));
	}
	
	/**
	 * @covers ::setCommand()
	 */
	public function testSetCommand()
	{
		$this->setUp('working');
		
		$command = self::$application->get($this->commandName);
		
		self::$command->setCommand($command);
		
		$this->assertSame($command, $this->readAttribute(self::$command, 'command'));
	}
	
	/**
	 * Data provider for testExecute().
	 * 
	 * @return array
	 */
	public function provide_testExecute_data()
	{
		return array(
			array('working', null, 'command_multikernel', array('--format' => 'json')),
			array('working', null, 'command_multikernel', array('--format' => 'md')),
			array('working', null, 'command_multikernel', array('--format' => 'txt')),
			array('working', null, 'command_multikernel', array('--format' => 'txt', '--raw' => true)),
			array('working', null, 'command_multikernel', array('--format' => 'xml')),
			array('working', 'app', 'command_appkernel', array('--format' => 'json')),
			array('working', 'app', 'command_appkernel', array('--format' => 'md')),
			array('working', 'app', 'command_appkernel', array('--format' => 'txt')),
			array('working', 'app', 'command_appkernel', array('--format' => 'txt', '--raw' => true)),
			array('working', 'app', 'command_appkernel', array('--format' => 'xml')),
		);
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultiKernelBundle\Test\CommandTestCase::convertParametersToOptions()
	 */
	protected static function convertParametersToOptions(array $parameters = array())
	{
		$options = array();
		
		if (isset($parameters['--raw'])) {
			$options['raw_text'] = $parameters['--raw'];
		}
		
		return $options;
	}
}
