<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Command;

use Motana\Bundle\MultikernelBundle\Command\MultikernelCommand;
use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\MultikernelCommand
 * @testdox Motana\Bundle\MultikernelBundle\Command\MultikernelCommand
 */
class MultikernelCommandTest extends CommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'debug:twig', [ 'filter' => 'even', '--format' => 'txt' ]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($app, $environment, $debug);
	}
	
	/**
	 * Adds a configured mock multikernel command including sub-commands.
	 *
	 * @param string $commandName Command name
	 * @param array $configuration Mock configuration
	 * @return MultiKernelCommand
	 */
	protected function createConfiguredMultiKernelCommand($commandName, array $configuration = [])
	{
		// Get the applications
		$applications = $this->callMethod(self::$application, 'getApplications');
		
		// Create a configured mock command for every application
		$commands = [];
		
		foreach ($applications as $kernelName => $application) {
			$application->add($commands[$kernelName] = $this->createConfiguredCommand($commandName, array_merge($configuration, [
				'isEnabled' => is_string($configuration['isEnabled']) ? $kernelName === $configuration['isEnabled'] : $configuration['isEnabled'],
				'isHidden' => is_string($configuration['isHidden']) ? $kernelName === $configuration['isHidden'] : $configuration['isHidden'],
			]), $application));
		}
		
		// Create and add a new multi-kernel command for the mocks
		self::$application->add($command = new MultikernelCommand($commandName, $commands));
		
		// Return the command
		return $command;
	}
	
	/**
	 * Create a configured mock command.
	 *
	 * @param string $commandName Command name
	 * @param array $configuration Mock configuration
	 * @param Application $application The application instance for the command
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	protected function createConfiguredCommand($commandName, array $configuration = [], Application $application)
	{
		// Create an input definition for the mock
		$definition = new InputDefinition([
			new InputArgument('dummy', InputArgument::OPTIONAL, 'dummy argument'),
		]);
		
		// Return a configured command mock
		return $this->createConfiguredMock(Command::class, array_merge([
			'getAliases' => [],
			'getApplication' => $application,
			'getDescription' => $commandName . ' command',
			'getHelp' => $commandName . ' command help',
			'getHelperSet' => $application->getHelperSet(),
			'getName' => $commandName,
			'getDefinition' => $definition,
			'getNativeDefinition' => $definition,
			'getUsages' => [],
		], $configuration));
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Check the properties are initialized correctly
		$this->assertEquals($this->commandName, $this->readAttribute(self::$command, 'name'));
		$this->assertEquals([ 'boot', 'app' ], array_keys($this->readAttribute(self::$command, 'commands')));
	}
	
	/**
	 * @covers ::getCommandForKernel()
	 * @testdox getCommandForKernel() returns commands for existing kernels
	 */
	public function test_getCommandForKernel_with_valid_kernel_names()
	{
		// Check the returned command is for the correct kernel
		$command = self::$command->getCommandForKernel('boot');
		$this->assertEquals('boot', $command->getApplication()->getKernel()->getName());
		
		// Check the returned command is for the correct kernel
		$command = self::$command->getCommandForKernel('app');
		$this->assertEquals('app', $command->getApplication()->getKernel()->getName());
	}
	
	/**
	 * @covers ::getCommandForKernel()
	 * @testdox getCommandForKernel() returns NULL for non-existing kernels
	 */
	public function test_getCommandForKernel_with_invalid_kernel_name()
	{
		// Check the return value is NULL for not existing kernels
		$this->assertNull(self::$command->getCommandForKernel('foobar'));
	}
	
	/**
	 * @covers ::getKernelName()
	 * @testdox getKernelName() returns the value of the kernelName property
	 */
	public function test_getKernelName()
	{
		// Set the kernel name property
		$this->writeAttribute(self::$command, 'kernelName', 'app');
		
		// Check the correct kernel name is returned
		$this->assertEquals('app', self::$command->getKernelName());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @testdox isEnabled() returns TRUE for commands enabled for all kernels
	 */
	public function test_isEnabled_for_all_kernels()
	{
		// Check the command is enabled
		$this->assertTrue(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @testdox isEnabled() returns TRUE for commands enabled for one kernel
	 */
	public function test_isEnabled_for_one_kernel()
	{
		// Create a configured multi-kernel command mock
		$command = $this->createConfiguredMultiKernelCommand('disabled', [
			'isEnabled' => 'boot',
			'isHidden' => false,
		]);
		
		// Check the command is enabled
		$this->assertTrue($command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "disabled" does not exist.
	 * @testdox isEnabled() returns FALSE for commands enabled for no kernel
	 */
	public function test_isEnabled_for_no_kernel()
	{
		// Create a configured multi-kernel command mock
		$command = $this->createConfiguredMultiKernelCommand('disabled', [
			'isEnabled' => false,
			'isHidden' => false,
		]);
		
		// Check the command is not enabled
		$this->assertFalse($command->isEnabled());
		
		// Check an exception is thrown when trying to retrieve the command by its name
		self::$application->get('disabled');
	}
	
	/**
	 * @covers ::isHidden()
	 * @testdox isHidden() returns FALSE for commands hidden for no kernel
	 */
	public function test_isHidden_for_no_kernel()
	{
		// Check the command is not hidden
		$this->assertFalse(self::$command->isHidden());
	}
	
	/**
	 * @covers ::isHidden()
	 * @testdox isHidden() returns FALSE for commands hidden for one kernel
	 */
	public function test_isHidden_for_one_kernel()
	{
		// Hide a command for one kernel
		$commands = $this->readAttribute(self::$command, 'commands');
		$commands['boot']->setHidden(true);
		
		// Check the command is not hidden
		$this->assertFalse(self::$command->isHidden());
	}
	
	/**
	 * @covers ::isHidden()
	 * @testdox isHidden() returns TRUE for commands hidden for all kernels
	 */
	public function test_isHidden_for_all_kernels()
	{
		// Hide a command for all kernels
		$commands = $this->readAttribute(self::$command, 'commands');
		$commands['boot']->setHidden(true);
		$commands['app']->setHidden(true);
		
		// Check the command is hidden
		$this->assertTrue(self::$command->isHidden());
	}
	
	/**
	 * @covers ::configure()
	 * @testdox configure() clones the configuration of wrapped commands
	 */
	public function test_configure()
	{
		// Create a configured multi-kernel command mock
		$command = $this->createConfiguredMultiKernelCommand('disabled', [
			'isEnabled' => false,
			'isHidden' => false,
			'getUsages' => [
				'disabled [options]',
			]
		]);
		
		// Check the mock is configured correctly
		$this->assertEquals([
			'name' => 'disabled',
			'aliases' => [],
			'definition' => new InputDefinition(),
			'description' => 'disabled command',
			'help' => 'disabled command help',
			'usages' => [ 'disabled [options]' ],
		], [
			'name' => $command->getName(),
			'aliases' => $command->getAliases(),
			'definition' => new InputDefinition(),
			'description' => $command->getDescription(),
			'help' => $command->getHelp(),
			'usages' => $command->getUsages(),
		]);
	}
	
	/**
	 * Data provider for test_execute().
	 *
	 * @return array
	 */
	public function provide_test_execute_data()
	{
		return [
			'multiple kernels (commandline help)' => [
				null,
				'command_multikernel',
				[
					'--help' => true
				]
			],
			'multiple kernels (commandline help for global command)' => [
				null,
				'command_multikernel',
				[
					'--help' => true,
					'--global' => true
				]
			],
			'multiple kernels (verbosity quiet)' => [
				null,
				'command_multikernel',
				[
					'--verbose' => Output::VERBOSITY_QUIET
				]
			],
			'multiple kernels (verbosity normal)' => [
				null,
				'command_multikernel',
				[
					'--verbose' => Output::VERBOSITY_NORMAL
				]
			],
			'multiple kernels (verbosity verbose)' => [
				null,
				'command_multikernel',
				[
					'--verbose' => Output::VERBOSITY_VERBOSE
				]
			],
			'multiple kernels (verbosity very verbose)' => [
				null,
				'command_multikernel',
				[
					'--verbose' => Output::VERBOSITY_VERY_VERBOSE
				]
			],
			'multiple kernels (verbosity debug)' => [
				null,
				'command_multikernel',
				[
					'--verbose' => Output::VERBOSITY_DEBUG
				]
			],
			'one kernel (commandline help)' => [
				null,
				'command_appkernel',
				[
					'kernel' => 'app',
					'--help' => true
				]
			],
			'one kernel (verbosity quiet)' => [
				null,
				'command_appkernel',
				[
					'kernel' => 'app',
					'--verbose' => Output::VERBOSITY_QUIET
				]
			],
			'one kernel (verbosity normal)' => [
				null,
				'command_appkernel',
				[
					'kernel' => 'app',
					'--verbose' => Output::VERBOSITY_NORMAL
				]
			],
			'one kernel (verbosity verbose)' => [
				null,
				'command_appkernel',
				[
					'kernel' => 'app',
					'--verbose' => Output::VERBOSITY_VERBOSE
				]
			],
			'one kernel (verbosity very verbose)' => [
				null,
				'command_appkernel',
				[
					'kernel' => 'app',
					'--verbose' => Output::VERBOSITY_VERY_VERBOSE
				]
			],
			'one kernel (verbosity debug)' => [
				null,
				'command_appkernel',
				[
					'kernel' => 'app',
					'--verbose' => Output::VERBOSITY_DEBUG
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
	 * @testdox execute() returns correct output for commands run on
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'debug:twig', 'even' ];
		
		if (isset($parameters['kernel'])) {
			array_splice($_SERVER['argv'], 1, 0, [ $parameters['kernel'] ]);
		}
		
		if (isset($parameters['--help'])) {
			$_SERVER['argv'][] = '--help';
		}
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Add command to the global commands list if required
		if (isset($parameters['--global'])) {
			$container = self::$application->getKernel()->getContainer();
			$containerParameters = $this->readAttribute($container, 'parameters');
			$containerParameters['motana.multikernel.commands.global'][] = 'debug:twig';
			$this->writeAttribute($container, 'parameters', $containerParameters);
		}
		
		// Get the requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Set output verbosity according to parameters
		$verbose = false;
		if (isset($parameters['--verbose'])) {
			self::$output->setVerbosity($parameters['--verbose']);
			$verbose = $parameters['--verbose'];
		}
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Run the command
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
	}
	
	/**
	 * Data provider for test_execute_skip_disabled().
	 *
	 * @return array
	 */
	public function provide_test_execute_skip_disabled_data()
	{
		return [
			'(verbosity quiet)' => [
				null,
				'command_disabled',
				[
					'--verbose' => Output::VERBOSITY_QUIET
				]
			],
			'(verbosity normal)' => [
				null,
				'command_disabled',
				[
					'--verbose' => Output::VERBOSITY_NORMAL
				]
			],
			'(verbosity verbose)' => [
				null,
				'command_disabled',
				[
					'--verbose' => Output::VERBOSITY_VERBOSE
				]
			],
			'(verbosity very verbose)' => [
				null,
				'command_disabled',
				[
					'--verbose' => Output::VERBOSITY_VERY_VERBOSE
				]
			],
			'(verbosity debug)' => [
				null,
				'command_disabled',
				[
					'--verbose' => Output::VERBOSITY_DEBUG
				]
			],
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_skip_disabled_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() skips commands on kernels they are disabled for
	 */
	public function test_execute_skip_disabled($app, $template, array $parameters = [])
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'disabled' ];
		
		// Set up a working environment of the requested type
		$this->setUp($app);

		// Create a configured multi-kernel command
		$command = $this->createConfiguredMultiKernelCommand('disabled', [
			'isEnabled' => 'boot',
			'isHidden' => false,
		]);
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Set output verbosity according to parameters
		$verbose = false;
		if (isset($parameters['--verbose'])) {
			self::$output->setVerbosity($parameters['--verbose']);
			$verbose = $parameters['--verbose'];
		}
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => 'disabled' ], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		// Run the command
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($output = $this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
		
		// Check the output contains the correct messages
		switch ($verbose) {
			case Output::VERBOSITY_DEBUG:
			case Output::VERBOSITY_VERY_VERBOSE:
			case Output::VERBOSITY_VERBOSE:
				$this->assertContains('Executing command on kernel boot...', $output);
				$this->assertContains('Skipping command on kernel app (command disabled)', $output);
				break;
			case Output::VERBOSITY_NORMAL:
				$this->assertContains('Executing command on kernel boot...', $output);
				break;
		}
	}

	/**
	 * Data provider for test_execute_skip_hidden().
	 *
	 * @return array
	 */
	public function provide_test_execute_skip_hidden_data()
	{
		return [
			'(verbosity quiet)' => [
				null,
				'command_hidden',
				[
					'--verbose' => Output::VERBOSITY_QUIET
				]
			],
			'(verbosity normal)' => [
				null,
				'command_hidden',
				[
					'--verbose' => Output::VERBOSITY_NORMAL
				]
			],
			'(verbosity verbose)' => [
				null,
				'command_hidden',
				[
					'--verbose' => Output::VERBOSITY_VERBOSE
				]
			],
			'(verbosity very verbose)' => [
				null,
				'command_hidden',
				[
					'--verbose' => Output::VERBOSITY_VERY_VERBOSE
				]
			],
			'(verbosity debug)' => [
				null,
				'command_hidden',
				[
					'--verbose' => Output::VERBOSITY_DEBUG
				]
			],
		];
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_skip_hidden_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() skips commands on kernels they are hidden for
	 */
	public function test_execute_skip_hidden($app, $template, array $parameters = [])
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'debug:twig', 'even' ];
		
		// Set up a working environment of the requested type
		$this->setUp($app);
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Set output verbosity according to parameters
		$verbose = false;
		if (isset($parameters['--verbose'])) {
			self::$output->setVerbosity($parameters['--verbose']);
			$verbose = $parameters['--verbose'];
		}
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => 'debug:twig' ], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		// Hide the command on the app kernel
		self::$command->getCommandForKernel('app')
			->setHidden(true);
		
		// Run the command
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($output = $this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
		
		// Check the output contains the correct messages
		switch ($verbose) {
			case Output::VERBOSITY_DEBUG:
			case Output::VERBOSITY_VERY_VERBOSE:
			case Output::VERBOSITY_VERBOSE:
				$this->assertContains('Executing command on kernel boot...', $output);
				$this->assertContains('Skipping command on kernel app (command disabled)', $output);
				break;
			case Output::VERBOSITY_NORMAL:
				$this->assertContains('Executing command on kernel boot...', $output);
				break;
		}
	}
	
	/**
	 * @covers ::execute()
	 * @testdox execute() prints an error message with BufferedOutput
	 */
	public function test_execute_exceptions_with_BufferedOutput()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'invalid_pool' ];
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => 'debug:container' ], $this->commandParameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		// Run the command
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		// Get command output
		$content = self::$output->fetch();
		
		// Expected exception name and message
		$expectedException = '[Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException]';
		$expectedExceptionMessage = 'You have requested a non-existent service "invalid_pool".';
		
		// Check the output contains messages for both the boot and app kernels
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertContains('Executing command on kernel app...', $content);
		
		// Check the output contains the expected exception name twice
		$this->assertEquals(2, substr_count($content, $expectedException));
		
		// Check the output contains the expected message twice
		$this->assertEquals(2, substr_count($content, $expectedExceptionMessage));
	}
	
	/**
	 * @covers ::execute()
	 * @testdox execute() prints an error message  with ConsoleOutput
	 */
	public function test_execute_exceptions_with_ConsoleOutput()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'invalid_pool' ];
		
		// Fake an environment not capable of using php://stdout
		$ostype = getenv('OSTYPE');
		putenv('OSTYPE=OS400');
		
		// Create a new console output
		$output = new ConsoleOutput(Output::VERBOSITY_QUIET, false);
		$output->setErrorOutput(self::$output);
		
		// Create an input for the command
		$parameters = array_merge([ 'command' => 'debug:container' ], $this->commandParameters);
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind inpot to the command definition
		$input->bind(self::$command->getDefinition());
		
		// Run the command
		self::$application->setAutoExit(false);
		self::$application->run($input, $output);
		
		// Get the error output
		$content = self::$output->fetch();
		
		// Expected exception name and message
		$expectedException = '[Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException]';
		$expectedExceptionMessage = 'You have requested a non-existent service "invalid_pool".';
		
		// Check the output contains the expected exception name
		$this->assertContains($expectedException, $content);
		
		// ...twice
		$this->assertEquals(2, substr_count($content, $expectedException));
		
		// Check the output contains the expected message
		$this->assertContains($expectedExceptionMessage, $content);
		
		// ...twice
		$this->assertEquals(2, substr_count($content, $expectedExceptionMessage));
		
		// Restore the previous OSTYPE environment variable
		putenv('OSTYPE=' . $ostype);
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
	 * @testdox run() returns correct output for commands run on
	 */
	public function test_run($app, $template, array $parameters = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'debug:twig', 'even' ];
		
		if (isset($parameters['kernel'])) {
			array_splice($_SERVER['argv'], 1, 0, [ $parameters['kernel'] ]);
		}
		
		if (isset($parameters['--help'])) {
			$_SERVER['argv'][] = '--help';
		}
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Add command to the global commands list if required
		if (isset($parameters['--global'])) {
			$container = self::$application->getKernel()->getContainer();
			$containerParameters = $this->readAttribute($container, 'parameters');
			$containerParameters['motana.multikernel.commands.global'][] = 'debug:twig';
			$this->writeAttribute($container, 'parameters', $containerParameters);
		}
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Set output verbosity according to parameters
		$verbose = false;
		if (isset($parameters['--verbose'])) {
			self::$output->setVerbosity($parameters['--verbose']);
			$verbose = $parameters['--verbose'];
		}
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
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
				case Output::VERBOSITY_QUIET:
					$options['quiet'] = $parameters['--verbose'];
					break;
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
		
		// Help option
		if (isset($parameters['--help'])) {
			$options['help'] = $parameters['--help'];
		}
		
		// Global command option
		if (isset($parameters['--global'])) {
			$options['global'] = $parameters['--global'];
		}
		
		// Return converted parameters
		return $options;
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::filterCommandParameters()
	 */
	protected static function filterCommandParameters(array $parameters = [])
	{
		// Remove the format and global parameters
		unset($parameters['--format']);
		unset($parameters['--global']);
		
		// Return the remaining parameters
		return $parameters;
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
		return parent::getTemplate($case, $options, $format, 'multikernel', $generateTemplates);
	}
}
