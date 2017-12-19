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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Routing\Route;

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
	 * @testdox isEnabled() returns TRUE for an Application
	 */
	public function test_isEnabled_with_AppKernel()
	{
		// Set up the AppKernel environment
		$this->setUp('app');
		
		// Override the application of the command with a single kernel application
		self::$command->setApplication(self::$application);
		
		// Check that isEnabled() returns TRUE
		$this->assertTrue(self::$command->isEnabled());
		
		// Remove the router service of the container
		$container = self::$kernel->getContainer();
		
		// Remove the router service from the method map so it cannot be reloaded
		$methodMap = $this->readAttribute($container, 'methodMap');
		unset($methodMap['router']);
		$this->writeAttribute($container, 'methodMap', $methodMap);
		
		// Remove the router service
		$services = $this->readAttribute($container, 'services');
		unset($services['router']);
		$this->writeAttribute($container, 'services', $services);
		
		// Check that isEnabled() returns FALSE
		$this->assertFalse(self::$command->isEnabled());
		
		// Insert an invalid replacement for the router service
		$services['router'] = new \stdClass();
		$this->writeAttribute($container, 'services', $services);
		
		// Check that isEnabled() returns FALSE
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
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/nomatch'
				]
			],
			'a path_info not matching an application (verbosity verbose)' => [
				null,
				'command_no_app_match',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/nomatch'
				]
			],
			'a path_info matching an application with no router (verbosity normal)' => [
				null,
				'command_no_router',
				[
					'--format' => 'txt',
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/boot'
				]
			],
			'a path_info matching an application with no router (verbosity verbose)' => [
				null,
				'command_no_router',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/boot'
				]
			],
			'a path_info matching an application with router, but no route (verbosity normal)' => [
				null,
				'command_no_route_match',
				[
					'--format' => 'txt',
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app/foo/bar'
				]
			],
			'a path_info matching an application with router, but no route (verbosity verbose)' => [
				null,
				'command_no_route_match',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app/foo/bar'
				]
			],
			'a path_info matching an application with router and a route (verbosity normal)' => [
				null,
				'command_with_route_match',
				[
					'--format' => 'txt',
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app'
				]
			],
			'a path_info matching an application with router and a route (verbosity verbose)' => [
				null,
				'command_with_route_match',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'--method' => 'GET',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app'
				]
			],
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() returns correct output for
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		parent::test_execute($app, $template, $parameters);
	}
	
	/**
	 * Data provider for test_execute_with_almost_matching_route().
	 *
	 * @return array
	 */
	public function provide_test_execute_with_almost_matching_route_data()
	{
		return [
			'a path info matching an application with router and an almost matching route (verbosity normal)' => [
				'command_with_almost_matching_route',
				[
					'--format' => 'txt',
					'--method' => 'POST',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app',
				]
			],
			'a path info matching an application with router and an almost matching route (verbosity verbose)' => [
				'command_with_almost_matching_route',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'--method' => 'POST',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app',
				]
			]
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_with_almost_matching_route_data
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() returns correct output for
	 */
	public function test_execute_with_almost_matching_route($template, array $parameters = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Modify the homepage route to match only the request method GET
		$container = self::$application->getApplication('app')->getKernel()->getContainer();
		$routes = $container->get('router')->getRouteCollection();
		$route = $routes->get('homepage');
		$route->setMethods('GET');
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Set kernel parameter
		$parameters['kernel'] = null;
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		self::$command->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
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
	 * @covers ::run()
	 * @covers ::execute()
	 * @dataProvider provide_test_run_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox run() returns correct output for
	 */
	public function test_run($app, $template, array $parameters = [])
	{
		parent::test_run($app, $template, $parameters);
	}
	
	/**
	 * Data provider for test_run_with_almost_matching_route().
	 *
	 * @return array
	 */
	public function provide_test_run_with_almost_matching_route_data()
	{
		return [
			'a path info matching an application with router and an almost matching route (verbosity normal)' => [
				'command_with_almost_matching_route',
				[
					'--format' => 'txt',
					'--method' => 'POST',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app',
				]
			],
			'a path info matching an application with router and an almost matching route (verbosity verbose)' => [
				'command_with_almost_matching_route',
				[
					'--format' => 'txt',
					'--verbose' => Output::VERBOSITY_VERBOSE,
					'--method' => 'POST',
					'--scheme' => 'https',
					'--host' => 'localhost',
					'path_info' => '/app',
				]
			]
		];
	}
	
	/**
	 * @covers ::run()
	 * @dataProvider provide_test_run_with_almost_matching_route_data
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() returns correct output for
	 */
	public function test_run_with_almost_matching_route($template, array $parameters = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Modify the homepage route to match only the request method GET
		$container = self::$application->getApplication('app')->getKernel()->getContainer();
		$routes = $container->get('router')->getRouteCollection();
		$route = $routes->get('homepage');
		$route->setMethods('GET');
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Set kernel parameter
		$parameters['kernel'] = null;
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		self::$command->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
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
