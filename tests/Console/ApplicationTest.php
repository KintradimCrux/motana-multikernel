<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console;

use Motana\Bundle\MultikernelBundle\Command\HelpCommand;
use Motana\Bundle\MultikernelBundle\Command\ListCommand;
use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Application
 * @testdox Motana\Bundle\MultikernelBundle\Console\Application
 */
class ApplicationTest extends ApplicationTestCase
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
		
		self::$application = new Application(self::$kernel);
		self::$output = new BufferedOutput();
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Check the autoExit property is initialized correctly
		$this->assertAttributeEquals(false, 'autoExit', self::$application);
	}
	
	/**
	 * @covers ::getName()
	 * @testdox getName() returns the multi-kernel app console signature
	 */
	public function test_getName()
	{
		// Check the returned name is correct
		$this->assertEquals('Motana Multi-Kernel App Console - Symfony', self::$application->getName());
	}
	
	/**
	 * @covers ::getDefaultCommands()
	 * @testdox getDefaultCommands() returns the 'help' and 'list' commands
	 */
	public function test_getDefaultCommands()
	{
		// Get the default commands
		$commands = $this->callMethod(self::$application, 'getDefaultCommands');
		
		// Check the method returned two commands
		$this->assertEquals(2, count($commands));
		
		// Check the two commands are instances of the correct classes
		$this->assertInstanceOf(HelpCommand::class, $commands[0]);
		$this->assertInstanceOf(ListCommand::class, $commands[1]);
	}
	
	/**
	 * @covers ::get()
	 * @testdox get() returns existing commands
	 */
	public function test_get()
	{
		// Test the correct help and list commands are returned
		$this->assertInstanceOf(HelpCommand::class, self::$application->get('help'));
		$this->assertInstanceOf(ListCommand::class, self::$application->get('list'));
	}
	
	/**
	 * @covers ::get()
	 * @testdox get() returns the 'help' command for existing commands called with the '--help' option
	 */
	public function test_get_with_help_option()
	{
		// Set the flag indicating that commandline help was requested
		$this->writeAttribute(self::$application, 'showHelp', true);
		
		// Request the list command
		$command = self::$application->get('list');
		
		// Check a help command was returned instead
		$this->assertInstanceOf(HelpCommand::class, $command);
		
		// Check the command property of the help command contains the list command
		$this->assertInstanceOf(ListCommand::class, $this->readAttribute($command, 'command'));
	}
	
	/**
	 * @covers ::get()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "invalid" does not exist.
	 * @testdox get() throws a CommandNotFoundException for non-existing commands
	 */
	public function test_get_with_not_existing_command()
	{
		// Check an exception is thrown when trying to get a non-existing command
		self::$application->get('invalid');
	}
	
	/**
	 * @covers ::get()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "help" does not exist.
	 * @testdox get() throws a CommandNotFoundException for hidden commands
	 */
	public function test_get_with_hidden_command()
	{
		// Hide the help command
		/** @var Command $command */
		$command = self::$application->get('help');
		$command->setHidden(true);
		
		// Check an exception is thrown when trying to get a hidden command
		self::$application->get('help');
	}
	
	/**
	 * @covers ::find()
	 * @testdox find() returns existing commands
	 */
	public function test_find()
	{
		// Check the correct help and list commands are returned
		$this->assertInstanceOf(HelpCommand::class, self::$application->find('help'));
		$this->assertInstanceOf(ListCommand::class, self::$application->find('list'));
	}

	/**
	 * @covers ::find()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage Command "invalid" is not defined.
	 * @testdox find() throws a CommandNotFoundException for non-existing commands
	 */
	public function test_find_with_not_existing_command()
	{
		// Check an exception is thrown when trying to find a non-existing command
		self::$application->find('invalid');
	}
	
	/**
	 * @covers ::find()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "help" does not exist.
	 * @testdox find() throws a CommandNotFoundException for hidden commands
	 */
	public function test_find_with_hidden_command()
	{
		// Hide the help command
		/** @var Command $command */
		$command = self::$application->find('help');
		$command->setHidden(true);
		
		// Check an exception is thrown when trying to find a hidden command
		self::$application->find('help');
	}
	
	/**
	 * @covers ::has()
	 * @testdox has() returns TRUE for existing commands
	 */
	public function test_has()
	{
		// Check that TRUE is returned for the help and list commands
		$this->assertTrue(self::$application->has('help'));
		$this->assertTrue(self::$application->has('list'));
	}
	
	/**
	 * @covers ::has()
	 * @testdox has() returns FALSE for non-existing commands
	 */
	public function test_has_with_not_existing_command()
	{
		// Check that FALSE is returned for non-existing commands
		$this->assertFalse(self::$application->has('invalid'));
	}
	
	/**
	 * @covers ::has()
	 * @testdox has() returns FALSE for hidden commands
	 */
	public function test_has_with_hidden_command()
	{
		// Hide the help command
		/** @var Command $command */
		$command = self::$application->find('help');
		$command->setHidden(true);
		
		// Check that FALSE is returned for a hidden command
		$this->assertFalse(self::$application->has('help'));
	}
	
	/**
	 * @covers ::all()
	 * @testdox all() returns all commands
	 */
	public function test_all()
	{
		$this->setUp('app');
		
		// Get all command names
		$commands = array_keys(self::$application->all());
		
		// Sort the command names
		sort($commands);
		
		// Check the correct command names are returned
		$this->assertEquals([
			'about',
			'assets:install',
			'cache:clear',
			'cache:pool:clear',
			'cache:warmup',
			'config:dump-reference',
			'debug:config',
			'debug:container',
			'debug:event-dispatcher',
			'debug:router',
			'debug:translation',
			'debug:twig',
			'help',
			'lint:twig',
			'lint:xliff',
			'lint:yaml',
			'list',
			'multikernel:convert',
			'router:match',
			'security:encode-password',
			'translation:update',
		], $commands);
	}
	
	/**
	 * @covers ::all()
	 * @testdox all() returns commands from a namespace
	 */
	public function test_all_with_namespace()
	{
		// Get all command names from the debug namespace
		$commands = array_keys(self::$application->all('debug'));
		
		// Sort the command names
		sort($commands);
		
		// Check the correct command names are returned
		$this->assertEquals([
			'debug:config',
			'debug:container',
			'debug:event-dispatcher',
			'debug:translation',
			'debug:twig',
		], $commands);
	}
	
	/**
	 * @covers ::all()
	 * @testdox all() does not return hidden commands
	 */
	public function test_all_with_hidden_command()
	{
		$this->setUp('app');
		
		// Hide the help command
		/** @var Command $command */
		$command = self::$application->find('help');
		$command->setHidden(true);
		
		// Get all command names
		$commands = array_keys(self::$application->all());
		
		// Sort the command names
		sort($commands);
		
		// Check the correct command names are returned
		$this->assertEquals([
			'about',
			'assets:install',
			'cache:clear',
			'cache:pool:clear',
			'cache:warmup',
			'config:dump-reference',
			'debug:config',
			'debug:container',
			'debug:event-dispatcher',
			'debug:router',
			'debug:translation',
			'debug:twig',
			'lint:twig',
			'lint:xliff',
			'lint:yaml',
			'list',
			'multikernel:convert',
			'router:match',
			'security:encode-password',
			'translation:update',
		], $commands);
	}
	
	/**
	 * @covers ::setDispatcher()
	 * @testdox setDispatcher() sets the dispatcher property
	 */
	public function test_setDispatcher()
	{
		// Create an event dispatcher
		$dispatcher = new EventDispatcher();
		
		// Set the event dispatcher of the application
		self::$application->setDispatcher($dispatcher);
		
		// Check the dispatcher property contains the same object
		$this->assertSame($dispatcher, $this->readAttribute(self::$application, 'dispatcher'));
	}
	
	/**
	 * @covers ::makePathRelative()
	 * @testdox makePathRelative() returns path relative to project directory
	 */
	public function test_makePathRelative()
	{
		// Check the returned path is correct
		$this->assertEquals('./src/AppBundle/Controller', $this->callMethod(self::$application, 'makePathRelative', self::$fixturesDir . '/src/AppBundle/Controller'));
	}
	
	/**
	 * @covers ::renderException()
	 * @testdox renderException() returns error message output
	 */
	public function test_renderException()
	{
		// Render an exception
		self::$application->renderException(new \InvalidArgumentException('The "test" argument does not exist.'), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the exception name
		$this->assertContains('[InvalidArgumentException]', $content);
		
		// Check the output contains the exception message
		$this->assertContains('The "test" argument does not exist.', $content);
	}
	
	/**
	 * @covers ::renderException()
	 * @testdox renderException() returns error message output with command synopsis if there is a running command
	 */
	public function test_renderException_with_running_command()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console' ];
		
		// Set the running command
		$this->writeAttribute(self::$application, 'runningCommand', self::$application->get('help'));
		
		// Render an exception
		self::$application->renderException(new \InvalidArgumentException('The "test" argument does not exist.'), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the exception name
		$this->assertContains('[InvalidArgumentException]', $content);
		
		// Check the output contains the exception message
		$this->assertContains('The "test" argument does not exist.', $content);
		
		// Check the output contains the command synopsis
		$this->assertContains('./bin/console boot help [--format FORMAT] [--raw] [--] [<command_name>]', $content);
	}
	
	/**
	 * @covers ::doRunCommand()
	 * @testdox doRunCommand() runs existing commands
	 */
	public function test_doRunCommand()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Create an input for the command and get the cache:pool:clear command
		$command = self::$application->get('cache:pool:clear');
		$input = new ArrayInput([ 'pools' => [ 'cache.global_clearer' ]]);
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRunCommand', $command, $input, self::$output);
		
		// Check the output contains messages from the cache:pool:clear command
		$content = self::$output->fetch();
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
	}
	
	/**
	 * @covers ::doRunCommand()
	 * @testdox doRunCommand() prints an error messsage on a BufferedOutput
	 */
	public function test_doRunCommand_error_with_BufferedOutput()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'invalid' ];
		
		// Create an input for the command and get the cache:pool:clearcommand
		$command = self::$application->get('cache:pool:clear');
		$input = new ArrayInput([ 'pools' => [ 'invalid' ]]);
		$input->bind($command->getDefinition());
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRunCommand', $command, $input, self::$output);
		
		// Get command output
		$content = self::$output->fetch();
		
		// Check the output contains the expected exception name
		$this->assertContains('[Symfony\\Component\\DependencyInjection\\Exception\\ServiceNotFoundException]', $content);
		
		// Check the output contains the expected message
		$this->assertContains('You have requested a non-existent service "invalid".', $content);
	}
	
	/**
	 * @covers ::doRunCommand()
	 * @testdox doRunCommand() prints an error message on a ConsoleOutput
	 */
	public function test_doRunCommand_error_with_ConsoleOutput()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'invalid' ];
		
		// Create an input for the command and get the cache:pool:clear command
		$command = self::$application->get('cache:pool:clear');
		$input = new ArrayInput([ 'pools' => [ 'invalid' ]]);
		$input->bind($command->getDefinition());

		// Fake an environment not capable of using php://stdout
		$ostype = getenv('OSTYPE');
		putenv('OSTYPE=OS400');
		
		// Create a new console output
		$output = new ConsoleOutput(Output::VERBOSITY_QUIET, false);
		$output->setErrorOutput(self::$output);
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRunCommand', $command, $input, $output);
		
		// Get the error output
		$content = self::$output->fetch();
		
		// Check the output contains the expected exception name
		$this->assertContains('[Symfony\\Component\\DependencyInjection\\Exception\\ServiceNotFoundException]', $content);
		
		// Check the output contains the expected message
		$this->assertContains('You have requested a non-existent service "invalid".', $content);
		
		// Restore the previous OSTYPE environment variable
		putenv('OSTYPE=' . $ostype);
	}

	/**
	 * @covers ::doRunCommand()
	 * @testdox doRunCommand() prints an error message for a PDO-like exception
	 */
	public function test_doRunCommand_error_with_PDO_like_exception()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'invalid' ];
		
		// Create an input for the command and get the cache:pool:clear command
		$input = new ArrayInput([]);
		
		// Create an exception that returns a string in getCode()
		$exception = new \Exception('My getCode() method returns a string.');
		$this->writeAttribute($exception, 'code', 'SQLSTATE[0815]: Nothing happened');
		
		// Create a command that throws the exception
		$command = (new Command('test'))
		->setDescription('ApplicationTest test command')
		->setCode(function(InputInterface $input, OutputInterface $output) use ($exception) {
			throw $exception;
		});
		
		// Add the command
		self::$application->add($command);
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRunCommand', $command, $input, self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the expected exception name
		$this->assertContains('[Exception]', $content);
		
		// Check the output contains the expected message
		$this->assertContains('My getCode() method returns a string.', $content);
	}
	
	/**
	 * @covers ::doRun()
	 * @testdox doRun() without arguments runs the default command ('list')
	 */
	public function test_doRun_without_arguments()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console' ];
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', new ArgvInput(), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the commandline help
		$this->assertContains('Usage:', $content);
		$this->assertContains('Options:', $content);
		$this->assertContains('Commands:', $content);
	}

	/**
	 * @covers ::doRun()
	 * @testdox doRun() shows commandline help when called with the '--help' option
	 */
	public function test_doRun_with_help_option()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', '--help' ];
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', new ArgvInput(), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the commandline help
		$this->assertContains('Usage:', $content);
		$this->assertContains('Arguments:', $content);
		$this->assertContains('Options:', $content);
		$this->assertContains('Help:', $content);
	}
	
	/**
	 * @covers ::doRun()
	 * @testdox doRun() shows the version signature when called with the '--version' or '-V' option
	 */
	public function test_doRun_with_version_option()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', '--version' ];
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', new ArgvInput(), self::$output);
		
		// Check the output is correct
		$this->assertEquals(sprintf("Motana Multi-Kernel App Console - Symfony %s (kernel: boot, env: test, debug: false)\n", Kernel::VERSION), self::$output->fetch());
	}
	
	/**
	 * @covers ::doRun()
	 * @testdox doRun() runs existing commands
	 */
	public function test_doRun()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', 'cache.global_clearer' ];
		
		// Create an input for the command
		$input = new ArrayInput([
			'command' => 'cache:pool:clear',
			'pools' => [ 'cache.global_clearer' ]
		]);
		
		// Merge the application definition and bind input to the definition
		$command = self::$application->get('cache:pool:clear');
		$command->mergeApplicationDefinition();
		$input->bind($command->getDefinition());
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', $input, self::$output);
		
		// Check the output contains messages from the cache:pool:clear command
		$content = self::$output->fetch();
		$this->assertContains('[OK] Cache was successfully cleared.', $content);
	}
	
	/**
	 * @covers ::doRun()
	 * @testdox doRun() with help option shows commandline help for a command
	 */
	public function test_doRun_with_command_and_help_option()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'cache:pool:clear', '--help' ];
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', new ArgvInput(), self::$output);
		
		// Get the output
		$content = self::$output->fetch();
		
		// Check the output contains the commandline help
		$this->assertContains('Usage:', $content);
		$this->assertContains('Arguments:', $content);
		$this->assertContains('Options:', $content);
		$this->assertContains('Help:', $content);
	}
	
	/**
	 * @covers ::doRun()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionCode 0
	 * @expectedExceptionMessage Command "invalid" is not defined.
	 * @testdox doRun() throws a CommandNotFoundException for non-existing commands
	 */
	public function test_doRun_with_not_existing_command()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'invalid' ];
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', new ArgvInput(), self::$output);
	}
	
	/**
	 * @covers ::doRun()
	 * @testdox doRun() handles a CommandNotFoundException with event dispatcher
	 */
	public function test_doRun_with_event_dispatcher()
	{
		// Fake a different script name and commandline for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = [ 'bin/console', 'invalid' ];
		
		// Get the event dispatcher and add a listener
		$dispatcher = self::$application->getKernel()->getContainer()->get('event_dispatcher');
		/** @var EventDispatcher $dispatcher */
		$dispatcher->addListener(ConsoleEvents::ERROR, [ $this, 'onConsoleError' ]);
		
		// Call the command
		$exitCode = $this->callMethod(self::$application, 'doRun', new ArgvInput(), self::$output);

		// Check the exit code was changed to 0 by the onConsoleError listener
		$this->assertEquals(0, $exitCode);
		
		// Check the command output is empty
		$this->assertEmpty(self::$output->fetch());
	}
	
	/**
	 * Event listener for the ConsoleEvents::ERROR event.
	 *
	 * @param ConsoleErrorEvent $event Error event
	 */
	public function onConsoleError(ConsoleErrorEvent $event)
	{
		// Override the exit code
		$event->setExitCode(0);
	}
}
