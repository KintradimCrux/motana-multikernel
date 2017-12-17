<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console;

use Motana\Bundle\MultikernelBundle\Command\GenerateAppCommand;
use Motana\Bundle\MultikernelBundle\Command\HelpCommand;
use Motana\Bundle\MultikernelBundle\Command\ListCommand;
use Motana\Bundle\MultikernelBundle\Command\MultikernelCommand;
use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Console\Input\ConditionalKernelArgument;
use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;

use Symfony\Bundle\FrameworkBundle\Command\ConfigDumpReferenceCommand;
use Symfony\Bundle\FrameworkBundle\Command\XliffLintCommand;
use Symfony\Bundle\FrameworkBundle\Command\YamlLintCommand;
use Symfony\Bundle\TwigBundle\Command\LintCommand as TwigLintCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\MultikernelApplication
 * @testdox Motana\Bundle\MultikernelBundle\Console\MultikernelApplication
 */
class MultikernelApplicationTest extends ApplicationTestCase
{
	/**
	 * Output of the tests.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($app, $environment, $debug);
		
		self::$output = new BufferedOutput();
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		$this->assertAttributeEquals(true, 'autoExit', self::$application);
	}
	
	/**
	 * @covers ::getApplications()
	 * @testdox getApplications() returns Application instances for existing kernels
	 */
	public function test_getApplications()
	{
		// Get all applications again
		$applications = self::$application->getApplications();
		
		// Check there are two applications
		$this->assertEquals(2, count($applications));
		
		// Check the kernel names are correct
		$this->assertEquals([ 'boot', 'app' ], array_keys($applications));
		
		// Check the applications are instances of the correct class
		$this->assertInstanceOf(Application::class, $applications['boot']);
		$this->assertInstanceOf(Application::class, $applications['app']);
		
		// Get the applications again
		$second = self::$application->getApplications();
		
		// Check there are two applications
		$this->assertEquals(2, count($second));
		
		// Check the kernel names are correct
		$this->assertEquals([ 'boot', 'app' ], array_keys($second));
		
		// Check the applications are instances of the correct class
		$this->assertInstanceOf(Application::class, $second['boot']);
		$this->assertInstanceOf(Application::class, $second['app']);
		
		// Check the applications are the same
		$this->assertSame($applications, $second);
	}
	
	/**
	 * @covers ::getApplication()
	 * @testdox getApplication() returns an Application instance for existing kernels
	 */
	public function test_getApplication()
	{
		// Check the application for the "boot" kernel is loaded
		$app = self::$application->getApplication('boot');
		$this->assertInstanceOf(Application::class, $app);
		
		// Check the application kernel returns the correct name
		$this->assertEquals('boot', $app->getKernel()->getName());
		
		// Check the application for the "app" kernel is loaded
		$app = self::$application->getApplication('app');
		$this->assertInstanceOf(Application::class, $app);
		
		// Check the application kernel returns the correct name
		$this->assertEquals('app', $app->getKernel()->getName());
	}
	
	/**
	 * @covers ::getApplication()
	 * @testdox getApplication() returns NULL for non-existing kernels
	 */
	public function test_getApplication_with_not_existing_kernel()
	{
		// Check the return value for a not existing kernel name is NULL
		$this->assertNull($this->callMethod(self::$application, 'getApplication', 'invalid'));
	}
	
	/**
	 * Data provider for test_removeKernelArgument().
	 *
	 * @return array
	 */
	public function provide_test_removeKernelArgument_data()
	{
		return [
			'an ArgvInput' => [
				ArgvInput::class,
				true,
				[
					'bin/console',
					'boot',
					'help'
				]
			],
			'an ArgvInput (no kernel argument specified)' => [
				ArgvInput::class,
				false,
				[
					'bin/console',
					'help'
				]
			],
			'an ArrayInput' => [
				ArrayInput::class,
				true,
				[
					'kernel' => 'boot',
					'command' => 'help'
				]
			],
			'an ArrayInput (no kernel argument specified)' => [
				ArrayInput::class,
				false,
				[
					'command' => 'help'
				]
			],
			'a StringInput' => [
				StringInput::class,
				true,
				'boot help'
			],
			'a StringInput (with options)' => [
				StringInput::class,
				true,
				'--no-ansi boot help'
			],
			'a StringInput (no kernel argument specified)' => [
				StringInput::class,
				false,
				'help'
			],
		];
	}
	
	/**
	 * @covers ::removeKernelArgument()
	 * @dataProvider provide_test_removeKernelArgument_data
	 * @param string $inputClassName Input class to test
	 * @param boolean $shift Remove the first non-option token from input
	 * @param mixed ...$ctorArgs Constructor parameters for the input
	 * @testdox removeKernelArgument() removes kernel argument from
	 */
	public function test_removeKernelArgument($inputClassName, $shift, ...$ctorArgs)
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Create the input class
		$class = new \ReflectionClass($inputClassName);
		$input = $class->newInstanceArgs($ctorArgs);
		
		// Bind the input to the application definition
		try {
			$input->bind(self::$application->getDefinition());
		} catch (\Exception $e) {
			
		}
		
		// Call the method
		$this->callMethod(self::$application, 'removeKernelArgument', $input, $shift);
		
		// Check the kernel argument has been removed
		$this->assertEquals([ 'command' ], array_keys(self::$application->getDefinition()->getArguments()));
		
		// Check the remaining arguments are not removed
		$this->assertEquals([ 'command' => 'help' ], $input->getArguments());
	}
	
	/**
	 * @covers ::getApplicationCommands()
	 * @testdox getApplicationCommands() returns correct commands
	 */
	public function test_getApplicationCommands()
	{
		self::$application->getApplications();
		$application = $this->callMethod(self::$application, 'getApplication', 'boot');
		$application->add($application->get('help')->setAliases([ 'help:help' ]));
		
		// Convert the method return value to a flat array
		$map = array_map(function($a) {
			return implode(':', array_keys($a));
		}, $this->callMethod(self::$application, 'getApplicationCommands'));
		
		// Sort the list
		ksort($map);
		
		// Check the commands are active in the correct apps
		$this->assertEquals([
			'about' => 'boot:app',
			'assets:install' => 'boot:app',
			'cache:clear' => 'boot:app',
			'cache:pool:clear' => 'boot:app',
			'cache:pool:prune' => 'boot:app',
			'cache:warmup' => 'boot:app',
			'config:dump-reference' => 'boot:app',
			'debug:autowiring' => 'boot:app',
			'debug:config' => 'boot:app',
			'debug:container' => 'boot:app',
			'debug:event-dispatcher' => 'boot:app',
			'debug:form' => 'app',
			'debug:router' => 'app',
			'debug:twig' => 'boot:app',
			'generate:app' => 'boot',
			'help' => 'boot:app',
			'lint:twig' => 'boot:app',
			'lint:xliff' => 'boot:app',
			'lint:yaml' => 'boot:app',
			'list' => 'boot:app',
			'router:match' => 'app',
			'security:encode-password' => 'app',
			'multikernel:convert' => 'boot:app',
		], $map);
	}
	
	/**
	 * @covers ::hideCommands()
	 * @testdox hideCommands() hides commands
	 */
	public function test_hideCommands()
	{
		// Create new help commands for the test
		$commands = [
			'boot' => new HelpCommand(),
			'app' => new HelpCommand(),
		];
		
		// Hide the commands
		$this->callMethod(self::$application, 'hideCommands', $commands);
		
		// Check both commands are hidden
		foreach ($commands as $command) {
			$this->assertTrue($command->isHidden());
		}
	}
	
	/**
	 * @covers ::hideCommands()
	 * @testdox hideCommands() hides global commands by name
	 */
	public function test_hideCommands_with_name()
	{
		// Create new help commands for the test
		$commands = [
			'boot' => new HelpCommand(),
			'app' => new HelpCommand(),
		];
		
		// Get the global help command
		$globalCommand = self::$application->get('help');
		
		// Hide the commands
		$this->callMethod(self::$application, 'hideCommands', $commands, 'help');
		
		// Check the commands are hidden
		foreach ($commands as $command) {
			$this->assertTrue($command->isHidden());
		}
		
		// Check the global command is hidden
		$this->assertTrue($globalCommand->isHidden());
	}
	
	/**
	 * @covers ::hideCommands()
	 * @testdox hideCommands() ignores invalid global command names
	 */
	public function test_hideCommands_with_invalid_name()
	{
		// Create new help commands for the test
		$commands = [
			'boot' => new HelpCommand(),
			'app' => new HelpCommand(),
		];
		
		// Hide the commands
		$this->callMethod(self::$application, 'hideCommands', $commands, 'invalid');
		
		// Check the commands are hidden
		foreach ($commands as $command) {
			$this->assertTrue($command->isHidden());
		}
	}
	
	/**
	 * @covers ::registerCommands()
	 * @testdox registerCommands() registers commands
	 */
	public function test_registerCommands()
	{
		// Call the method
		$this->callMethod(self::$application, 'registerCommands');
		
		// Check commands are registered
		$this->assertAttributeEquals(true, 'commandsRegistered', self::$application);
		
		// Check the help command is an instance of the correct class
		$this->assertInstanceOf(HelpCommand::class, self::$application->find('help'));
		
		// Check the list command is an instance of the correct class
		$this->assertInstanceOf(ListCommand::class, self::$application->find('list'));
	}
	
	/**
	 * @covers ::registerCommands()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "debug:config" does not exist.
	 * @testdox registerCommands() hides commands
	 */
	public function test_registerCommands_hides_commands()
	{
		// Update container parameters
		$container = self::$application->getKernel()->getContainer();
		$parameters = array_merge($this->getObjectAttribute($container, 'parameters'), [
			'motana.multikernel.commands.hidden' => [
				'help',
				'list',
				'debug:config',
			],
		]);
		$this->writeAttribute($container, 'parameters', $parameters);
		
		// Register commands again
		$this->writeAttribute(self::$application, 'commandsRegistered', false);
		$this->callMethod(self::$application, 'registerCommands');
		
		// Check that the help and list commands are available
		self::$application->get('help');
		self::$application->get('list');
		
		// Check that the debug:config command is hidden
		self::$application->get('debug:config');
	}
	
	/**
	 * Data provider for test_registerCommands_result().
	 *
	 * @return array
	 */
	public function provide_test_registerCommands_result_data()
	{
		return [
			// Commands that are left unchanged
			'\'help\'' => [
				HelpCommand::class,
				'help'
			],
			'\'list\'' => [
				ListCommand::class,
				'list'
			],
			'\'config:dump-reference\'' => [
				ConfigDumpReferenceCommand::class,
				'config:dump-reference'
			],
			'\'lint:twig\'' => [
				TwigLintCommand::class,
				'lint:twig'
			],
			'\'lint:yaml\'' => [
				YamlLintCommand::class,
				'lint:yaml'
			],
			'\'lint:xliff\'' => [
				XliffLintCommand::class,
				'lint:xliff'
			],
			
			// Standard Symfony commands wrapped into multi-kernel command instances
			'\'assets:install\'' => [
				MultiKernelCommand::class,
				'assets:install'
			],
			'\'cache:clear\'' => [
				MultiKernelCommand::class,
				'cache:clear'
			],
			'\'cache:pool:clear\'' => [
				MultiKernelCommand::class,
				'cache:pool:clear'
			],
			'\'cache:pool:prune\'' => [
				MultiKernelCommand::class,
				'cache:pool:prune'
			],
			'\'cache:warmup\'' => [
				MultiKernelCommand::class,
				'cache:warmup'
			],
			'\'debug:autowiring\'' => [
				MultiKernelCommand::class,
				'debug:autowiring'
			],
			'\'debug:config\'' => [
				MultiKernelCommand::class,
				'debug:config'
			],
			'\'debug:container\'' => [
				MultiKernelCommand::class,
				'debug:container'
			],
			'\'debug:event-dispatcher\'' => [
				MultiKernelCommand::class,
				'debug:event-dispatcher'
			],
			
			// Multi-kernel bundle commands
			'\'generate:app\'' => [
				GenerateAppCommand::class,
				'generate:app'
			],
		];
	}
	
	/**
	 * @covers ::registerCommands()
	 * @dataProvider provide_test_registerCommands_result_data
	 * @param string $expectedClassName Expected command class name
	 * @param string $command Command name
	 * @testdox registerCommands() registered command
	 */
	public function test_registerCommands_result($expectedClassName, $command)
	{
		// Check the command is an instance of the expected class
		$this->assertInstanceOf($expectedClassName, self::$application->get($command));
	}
	
	/**
	 * @covers ::getDefaultInputDefinition()
	 * @testdox getDefaultInputDefinition() returns correct arguments and options
	 */
	public function test_getDefaultInputDefinition()
	{
		$definition = $this->callMethod(self::$application, 'getDefaultInputDefinition');
		/** @var InputDefinition $definition */
		
		// Check the return value is an instance of the correct class
		$this->assertInstanceOf(InputDefinition::class, $definition);
		
		$arguments = array_values($definition->getArguments());
		$options = array_values($definition->getOptions());
		
		// Check the arguments are correct
		$this->assertEquals(2, count($arguments));
		$this->assertInputArgument($arguments[0], ConditionalKernelArgument::class, 'kernel', InputArgument::REQUIRED, 'The kernel to execute');
		$this->assertInputArgument($arguments[1], InputArgument::class, 'command', InputArgument::REQUIRED, 'The command to execute');
		
		// Check the options are correct
		$this->assertEquals(7, count($options));
		$this->assertInputOption($options[0], InputOption::class, 'help', 'h', InputOption::VALUE_NONE, 'Display this help message');
		$this->assertInputOption($options[1], InputOption::class, 'quiet', 'q', InputOption::VALUE_NONE, 'Do not output any message');
		$this->assertInputOption($options[2], InputOption::class, 'verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug');
		$this->assertInputOption($options[3], InputOption::class, 'version', 'V', InputOption::VALUE_NONE, 'Display this application version');
		$this->assertInputOption($options[4], InputOption::class, 'ansi', '', InputOption::VALUE_NONE, 'Force ANSI output');
		$this->assertInputOption($options[5], InputOption::class, 'no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output');
		$this->assertInputOption($options[6], InputOption::class, 'no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question');
	}

	/**
	 * @covers ::doRun()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage A MultikernelApplication requires a Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput to work
	 * @testdox doRun() checks the input type
	 */
	public function test_doRun_with_invalid_input()
	{
		// Run the application
		self::$application->doRun($this->createMock(InputInterface::class), new BufferedOutput());
	}

	/**
	 * @covers ::doRun()
	 * @testdox doRun() shows error message with too many arguments
	 */
	public function test_doRun_with_too_many_arguments()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'boot', 'debug:config', 'too', 'many', 'arguments' ];
		
		// Run the application
		self::$application->doRun($input = new ArgvInput(), $output = new BufferedOutput());
		
		// Get command output
		$content = $output->fetch();
		
		// Check the output contains the exception location
		$this->assertRegExp('|In ArgvInput.php line \d+|', $content);
		
		// Check the output contains the exception message
		$this->assertContains('Too many arguments, expected arguments: "command" "name" "path".', $content);
		
		// Check the output contains the long command synopsis
		$this->assertContains('./bin/console boot debug:config [-h|--help] [-q|--quiet] [-v|vv|vvv|--verbose] [-V|--version] [--ansi] [--no-ansi] [-n|--no-interaction] [-e|--env ENV] [--no-debug] [--] [<name>] [<path>]', $content);
	}
	
	/**
	 * @covers ::doRun()
	 * @covers ::doRunSingleKernel()
	 * @testdox doRun() runs command on one kernel
	 */
	public function test_doRun_single_kernel()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'boot', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Run the application
		self::$application->doRun($input = new ArgvInput(), $output = new BufferedOutput());
		
		// Get the output
		$content = $output->fetch();
		
		// Check the output contains a message for the boot kernel
		$this->assertContains('Executing command on kernel boot...', $content);
		
		// Check the output contains no message for the app kernel
		$this->assertNotContains('Executing command on kernel app...', $content);
		
		// Check the output contains the message from the command
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
		
		// ...once
		$this->assertEquals(1, substr_count($content, '[OK] Cache was successfully cleared.'));
	}

	/**
	 * @covers ::doRun()
	 * @covers ::doRunMultiKernel()
	 * @testdox doRun() runs commands on all kernels
	 */
	public function test_doRun_multi_kernel()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Run the application
		self::$application->doRun($input = new ArgvInput(), $output = new BufferedOutput());

		// Get command output
		$content = $output->fetch();
		
		// Check the output contains a message for both the boot and app kernels
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertContains('Executing command on kernel app...', $content);
		
		// Check the output contains the message from the command
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
		
		// ...twice
		$this->assertEquals(2, substr_count($content, '[OK] Cache was successfully cleared.'));
	}
	
	/**
	 * @covers ::run()
	 * @testdox run() runs commands on one kernel
	 */
	public function test_run_single_kernel()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'boot', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Run the application
		self::$application->setAutoExit(false);
		self::$application->run($input = new ArgvInput(), $output = new BufferedOutput());
		
		// Get command output
		$content = $output->fetch();
		
		// Check the output contains a message for the boot kernel
		$this->assertContains('Executing command on kernel boot...', $content);
		
		// Check the output contains no message for the app kernel
		$this->assertNotContains('Executing command on kernel app...', $content);
		
		// Check the output contains the message from the command
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
		
		// ...once
		$this->assertEquals(1, substr_count($content, '[OK] Cache was successfully cleared.'));
	}
	
	/**
	 * @covers ::run()
	 * @testdox run() runs commands on all kernels
	 */
	public function test_run_multi_kernel()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Run the application
		self::$application->setAutoExit(false);
		self::$application->run($input = new ArgvInput(), $output = new BufferedOutput());
		
		// Get command output
		$content = $output->fetch();
		
		// Check the output contains a message for both the boot and app kernels
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertContains('Executing command on kernel app...', $content);
		
		// Check the output contains the message from the command
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
		
		// ...twice
		$this->assertEquals(2, substr_count($content, '[OK] Cache was successfully cleared.'));
	}
	
	/**
	 * @covers ::run()
	 * @testdox run() skips commands on kernels they are disabled or hidden for
	 */
	public function test_run_skips_commands()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'cache.global_clearer', '-v' ];
		
		$multikernelCommand = self::$application->get('cache:pool:clear');
		
		$command = $multikernelCommand->getCommandForKernel('boot');
		$command->setHidden(true);
		
		$this->assertFalse($multikernelCommand->isHidden());
		
		// Run the application
		self::$application->setAutoExit(false);
		self::$application->run($input = new ArgvInput(), $output = new BufferedOutput());
		
		// Get command output
		$content = $output->fetch();

		// Check the output contains a message for the app kernel
		$this->assertContains('Skipping command on kernel boot (command disabled)', $content);
		$this->assertContains('Executing command on kernel app...', $content);
		
		// Check the output contains the message from the command
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
		
		// ...once
		$this->assertEquals(1, substr_count($content, '[OK] Cache was successfully cleared.'));
	}
	
	/**
	 * @covers ::run()
	 * @testdox run() creates an input if required
	 */
	public function test_run_creates_input()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'boot', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Run the application
		self::$application->setAutoExit(false);
		self::$application->run(null, $output = new BufferedOutput());
		
		// Get command output
		$content = $output->fetch();
		
		// Check the output contains a message for the boot kernel
		$this->assertContains('Executing command on kernel boot...', $content);
		
		// Check the output contains the message from the command
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
		
		// ...once
		$this->assertEquals(1, substr_count($content, '[OK] Cache was successfully cleared.'));
	}
	
	/**
	 * @covers ::renderException()
	 * @testdox renderException() returns correct output
	 */
	public function test_renderException()
	{
		// Render an exception
		self::$application->renderException(new \InvalidArgumentException('The "test" argument does not exist.'), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the exception location
		$this->assertRegExp('|In MultikernelApplicationTest.php line \d+|', $content);
		
		// Check the output contains the exception message
		$this->assertContains('The "test" argument does not exist.', $content);
	}
	
	/**
	 * @covers ::renderException()
	 * @testdox renderException() returns correct output with command synopsis of a running command
	 */
	public function test_renderException_with_running_command()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'debug:config' ];
		
		// Set the running command
		$command = self::$application->get('debug:config');
		
		// Set the running command
		$command = self::$application->get('debug:config');
		$this->writeAttribute(self::$application, 'runningCommand', $command);
		
		// Render an exception
		self::$application->renderException(new \InvalidArgumentException('The "test" argument does not exist.'), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the exception location
		$this->assertRegExp('|In MultikernelApplicationTest.php line \d+|', $content);
		
		// Check the output contains the exception message
		$this->assertContains('The "test" argument does not exist.', $content);
		
		// Check the output contains the command synopsis
		$this->assertContains('./bin/console debug:config [<name>] [<path>]', $content);
	}
	
	/**
	 * @covers ::renderException()
	 * @testdox renderException() returns correct output with command synopsis of a running command (with kernel name)
	 */
	public function test_renderException_with_running_command_and_kernel_name()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'debug:config' ];
		
		// Set the running command
		$command = self::$application->get('debug:config');
		$this->writeAttribute($command, 'kernelName', 'boot');
		$this->writeAttribute(self::$application, 'runningCommand', $command);
		
		// Render an exception
		self::$application->renderException(new \InvalidArgumentException('The "test" argument does not exist.'), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the exception location
		$this->assertRegExp('|In MultikernelApplicationTest.php line \d+|', $content);
		
		// Check the output contains the exception message
		$this->assertContains('The "test" argument does not exist.', $content);
		
		// Check the output contains the command synopsis
		$this->assertContains('./bin/console boot debug:config [<name>] [<path>]', $content);
	}
}
