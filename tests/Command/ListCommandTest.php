<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Command\ListCommand;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\ListCommand
 * @testdox Motana\Bundle\MultikernelBundle\Command\ListCommand
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
	 * Data provider for test_execute().
	 *
	 * @return array
	 */
	public function provide_test_execute_data()
	{
		return [
			'a MultikernelApplication instance (json format)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'json'
				]
			],
			'a MultikernelApplication instance (json format, namespace debug)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'json',
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication instance (md format)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'md'
				]
			],
			'a MultikernelApplication instance (md format, namespace debug)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'md',
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication instance (text format)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'txt'
				]
			],
			'a MultikernelApplication instance (text format, namespace debug)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'txt',
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication instance (raw text format)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'txt',
					'--raw' => true
				]
			],
			'a MultikernelApplication instance (raw text format, namespace debug)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'txt',
					'--raw' => true,
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication instance (xml format)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'xml'
				]
			],
			'a MultikernelApplication instance (xml format, namespace debug)' => [
				null,
				'command_multikernel',
				[
					'--format' => 'xml',
					'namespace' => 'debug'
				]
			],
			'an Application instance (json format)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'json'
				]
			],
			'an Application instance (json format, namespace debug)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'json',
					'namespace' => 'debug'
				]
			],
			'an Application instance (md format)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'md'
				]
			],
			'an Application instance (md format, namespace debug)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'md',
					'namespace' => 'debug'
				]
			],
			'an Application instance (text format)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'txt'
				]
			],
			'an Application instance (text format, namespace debug)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'txt',
					'namespace' => 'debug'
				]
			],
			'an Application instance (raw text format)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'txt',
					'--raw' => true
				]
			],
			'an Application instance (raw text format, namespace debug)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'txt',
					'--raw' => true,
					'namespace' => 'debug'
				]
			],
			'an Application instance (xml format)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'xml'
				]
			],
			'an Application instance (xml format, namespace debug)' => [
				'app',
				'command_appkernel',
				[
					'--format' => 'xml',
					'namespace' => 'debug'
				]
			],
		];
	}
	
	/**
	 * Data provider for test_run().
	 *
	 * @return array
	 */
	public function provide_test_run_data()
	{
		return $this->provide_test_execute_data();
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::convertParametersToOptions()
	 */
	protected static function convertParametersToOptions(array $parameters = [])
	{
		$options = [];
		
		if (isset($parameters['namespace'])) {
			$options['namespace'] = $parameters['namespace'];
		}
		
		return $options;
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format
	 * @param string $commandName Command name
	 * @param boolean $generateTemplates Boolean indicating to generate templates
	 * @return string
	 */
	protected static function getTemplate($case, array $options = [], $format, $commandName, $generateTemplates = false)
	{
		return parent::getTemplate($case, $options, $format, $commandName, $generateTemplates);
	}
}
