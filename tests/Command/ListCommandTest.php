<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Command;

use Motana\Bundle\MultikernelBundle\Command\ListCommand;
use Motana\Bundle\MultikernelBundle\Test\CommandTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\ListCommand
 */
class ListCommandTest extends CommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'list');
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
			array('working', null, 'command_multikernel', array('--format' => 'json', 'namespace' => 'debug')),
			array('working', null, 'command_multikernel', array('--format' => 'md')),
			array('working', null, 'command_multikernel', array('--format' => 'md', 'namespace' => 'debug')),
			array('working', null, 'command_multikernel', array('--format' => 'txt')),
			array('working', null, 'command_multikernel', array('--format' => 'txt', 'namespace' => 'debug')),
			array('working', null, 'command_multikernel', array('--format' => 'txt', '--raw' => true)),
			array('working', null, 'command_multikernel', array('--format' => 'txt', '--raw' => true, 'namespace' => 'debug')),
			array('working', null, 'command_multikernel', array('--format' => 'xml')),
			array('working', null, 'command_multikernel', array('--format' => 'xml', 'namespace' => 'debug')),
			array('working', 'app', 'command_appkernel', array('--format' => 'json')),
			array('working', 'app', 'command_appkernel', array('--format' => 'json', 'namespace' => 'debug')),
			array('working', 'app', 'command_appkernel', array('--format' => 'md')),
			array('working', 'app', 'command_appkernel', array('--format' => 'md', 'namespace' => 'debug')),
			array('working', 'app', 'command_appkernel', array('--format' => 'txt')),
			array('working', 'app', 'command_appkernel', array('--format' => 'txt', 'namespace' => 'debug')),
			array('working', 'app', 'command_appkernel', array('--format' => 'txt', '--raw' => true)),
			array('working', 'app', 'command_appkernel', array('--format' => 'txt', '--raw' => true, 'namespace' => 'debug')),
			array('working', 'app', 'command_appkernel', array('--format' => 'xml')),
			array('working', 'app', 'command_appkernel', array('--format' => 'xml', 'namespace' => 'debug')),
		);
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Test\CommandTestCase::convertParametersToOptions()
	 */
	protected static function convertParametersToOptions(array $parameters = array())
	{
		$options = array();
		
		if (isset($parameters['namespace'])) {
			$options['namespace'] = $parameters['namespace'];
		}
		if (isset($parameters['--raw'])) {
			$options['raw_text'] = $parameters['--raw'];
		}
		
		return $options;
	}
}
