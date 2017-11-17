<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\RouterMatchCommand
 * @testdox Motana\Bundle\MultikernelBundle\Command\RouterMatchCommand
 */
class RouterMatchCommandTest extends CommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'router:match');
	}
	
	/**
	 * @covers ::isEnabled()
	 * @testdox isEnabled() returns TRUE for a MultikernelApplication
	 */
	public function test_isEnabled()
	{
		// Check that isEnabled() returns TRUE for a MultikernelApplication
		$this->assertTrue(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @testdox isEnabled() returns FALSE for an Application
	 */
	public function test_isEnabled_with_appkernel()
	{
		// Override the application of the command with a single kernel application
		self::$command->setApplication(self::$application->getApplication('app'));
		
		// Check that isEnabled() returns FALSE now
		$this->assertFalse(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::getNativeDefinition()
	 * @testdox getNativeDefinition() returns the correct input definition
	 */
	public function test_getNativeDefinition()
	{
		// Get input arguments and options
		$definition = self::$command->getNativeDefinition();
		$arguments = array_values($definition->getArguments());
		$options = array_values($definition->getOptions());
		
		// Check the arguments are correct
		$this->assertEquals(1, count($arguments));
		$this->assertInputArgument($arguments[0], InputArgument::class, 'path_info', InputArgument::REQUIRED, 'A path info');
		
		// Check the options are correct
		$this->assertEquals(3, count($options));
		$this->assertInputOption($options[0], InputOption::class, 'method', null, InputOption::VALUE_REQUIRED, 'Sets the HTTP method');
		$this->assertInputOption($options[1], InputOption::class, 'scheme', null, InputOption::VALUE_REQUIRED, 'Sets the URI scheme (usually http or https)');
		$this->assertInputOption($options[2], InputOption::class, 'host', null, InputOption::VALUE_REQUIRED, 'Sets the URI host');
	}
	
	/**
	 * @covers ::configure()
	 * @testdox configure() configures the command
	 */
	public function test_configure()
	{
		// Check the command name has been initialized correctly
		$this->assertEquals('router:match', self::$command->getName());
		
		// Check the command description has been initialized correctly
		$this->assertEquals('Helps debug routes by simulating a path info match', self::$command->getDescription());
		
		// Check the command help has been initialized correctly
		$this->assertEquals(<<<EOH
The <info>%command.name%</info> shows which routes match a given request and which don't and for what reason:

  <info>php %command.full_name% /foo</info>

or

  <info>php %command.full_name% /foo --method POST --scheme https --host symfony.com --verbose</info>
EOH
		, self::$command->getHelp());
		
		// Get input arguments and options
		$definition = self::$command->getNativeDefinition();
		$arguments = array_values($definition->getArguments());
		$options = array_values($definition->getOptions());
		
		// Check the arguments are correct
		$this->assertEquals(1, count($arguments));
		$this->assertInputArgument($arguments[0], InputArgument::class, 'path_info', InputArgument::REQUIRED, 'A path info');
		
		// Check the options are correct
		$this->assertEquals(3, count($options));
		$this->assertInputOption($options[0], InputOption::class, 'method', null, InputOption::VALUE_REQUIRED, 'Sets the HTTP method');
		$this->assertInputOption($options[1], InputOption::class, 'scheme', null, InputOption::VALUE_REQUIRED, 'Sets the URI scheme (usually http or https)');
		$this->assertInputOption($options[2], InputOption::class, 'host', null, InputOption::VALUE_REQUIRED, 'Sets the URI host');
	}
	
	/**
	 * Data provider for test_execute().
	 *
	 * @return array
	 */
	public function provide_test_execute_data()
	{
		return [
			'a path_info not matching an application (verbosity normal)' => [
				null,
				'command_no_app_match',
				[
					'--format' => 'txt',
					'path_info' => '/nomatch'
				]
			],
			'a path_info not matching an application (verbosity verbose)' => [
				null,
				'command_no_app_match',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'path_info' => '/nomatch'
				]
			],
			'a path_info matching an application with no router (verbosity normal)' => [
				null,
				'command_no_router',
				[
					'--format' => 'txt',
					'path_info' => '/boot'
				]
			],
			'a path_info matching an application with no router (verbosity verbose)' => [
				null,
				'command_no_router',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'path_info' => '/boot'
				]
			],
			'a path_info matching an application with router, but no route (verbosity normal)' => [
				null,
				'command_no_route_match',
				[
					'--format' => 'txt',
					'path_info' => '/app/foo/bar'
				]
			],
			'a path_info matching an application with router, but no route (verbosity verbose)' => [
				null,
				'command_no_route_match',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'path_info' => '/app/foo/bar'
				]
			],
			'a path_info matching an application with router and a route (verbosity normal)' => [
				null,
				'command_with_route_match',
				[
					'--format' => 'txt',
					'path_info' => '/app'
				]
			],
			'a path_info matching an application with router and a route (verbosity verbose)' => [
				null,
				'command_with_route_match',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'path_info' => '/app'
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
		
		// Verbose option
		if (isset($parameters['--verbose'])) {
			switch ($parameters['--verbose']) {
				case Output::VERBOSITY_NORMAL:
					$options['normal'] = $parameters['--verbose'];
					break;
				case Output::VERBOSITY_VERBOSE:
					$options['verbose'] = $parameters['--verbose'];
					break;
				case Output::VERBOSITY_VERY_VERBOSE:
					$options['very_verbose'] = $parameters['--verbose'];
					break;
				case Output::VERBOSITY_DEBUG:
					$options['debug'] = $parameters['--verbose'];
					break;
			}
		}
		
		// Return converted parameters
		return $options;
	}
	
	/**
	 * Filters input command parameters before binding input.
	 *
	 * @param array $parameters Parameters to filter
	 * @return unknown
	 */
	protected static function filterCommandParameters(array $parameters = [])
	{
		// Copy input parameters
		$filtered = $parameters;
		
		// Remove the format option
		unset($filtered['--format']);
		
		// Return the filtered parameters
		return $filtered;
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
