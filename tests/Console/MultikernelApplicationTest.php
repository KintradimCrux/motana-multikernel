<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Console;

use Symfony\Bundle\FrameworkBundle\Command\YamlLintCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Motana\Bundle\MultikernelBundle\Command\HelpCommand;
use Motana\Bundle\MultikernelBundle\Command\ListCommand;
use Motana\Bundle\MultikernelBundle\Command\MultikernelCommand;
use Motana\Bundle\MultikernelBundle\Command\MultikernelCreateAppCommand;
use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;
use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Console\Input\KernelArgument;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\MultiKernelApplication
 */
class MultikernelApplicationTest extends ApplicationTestCase
{
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
	}
	
	/**
	 * Assert that an InputArgument has the specified properties.
	 *
	 * @param InputArgument $argument The argument to inspect
	 * @param string $expectedClass Expected class name
	 * @param string $argumentName Expected argument name
	 * @param integer $argumentMode Expected argument mode
	 * @param string $argumentDescription Expected argument description
	 */
	public static function assertInputArgument(InputArgument $argument, $expectedClass, $argumentName, $argumentMode, $argumentDescription)
	{
		self::assertInstanceOf($expectedClass, $argument);
		
		self::assertEquals($argumentName, $argument->getName());
		
		self::assertAttributeEquals($argumentMode, 'mode', $argument);
		
		self::assertEquals($argumentDescription, $argument->getDescription());
	}
	
	/**
	 * Assert that an Inputoption has the specified properties.
	 *
	 * @param InputOption $option The option to inspect
	 * @param string $expectedClass Expected class name
	 * @param string $optionName Expected option name
	 * @param string $optionShortcut Expected option shortcut
	 * @param integer $optionMode Expected option mode
	 * @param string $optionDescription Expected option description
	 */
	public static function assertInputOption(InputOption $option, $expectedClass, $optionName, $optionShortcut, $optionMode, $optionDescription)
	{
		self::assertInstanceOf($expectedClass, $option);
		
		self::assertEquals($optionName, $option->getName());
		
		self::assertEquals($optionShortcut, $option->getShortcut());
		
		self::assertAttributeEquals($optionMode, 'mode', $option);
		
		self::assertEquals($optionDescription, $option->getDescription());
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertAttributeEquals(true, 'autoExit', self::$application);
	}
	
	/**
	 * @covers ::getApplications()
	 */
	public function testGetApplications()
	{
		$applications = $this->callMethod(self::$application, 'getApplications');
		
		// Check that there are two applications
		$this->assertEquals(2, count($applications));
		
		// Check the kernel names are correct
		$this->assertEquals(array('boot', 'app'), array_keys($applications));
		
		// Check the applications are instances of the correct class
		$this->assertInstanceOf(Application::class, $applications['boot']);
		$this->assertInstanceOf(Application::class, $applications['app']);
	}
	
	/**
	 * @covers ::getApplication()
	 */
	public function testGetApplication()
	{
		// Check that the application for the "boot" kernel is loaded
		$app = $this->callMethod(self::$application, 'getApplication', 'boot');
		$this->assertInstanceOf(Application::class, $app);
		$this->assertEquals('boot', $app->getKernel()->getName());
		
		// Check that the application for the "app" kernel is loaded
		$app = $this->callMethod(self::$application, 'getApplication', 'app');
		$this->assertInstanceOf(Application::class, $app);
		$this->assertEquals('app', $app->getKernel()->getName());
	}
	
	/**
	 * Data provider for testRemoveKernelArgument().
	 *
	 * @return array
	 */
	public function provide_testRemoveKernelArgument_data()
	{
		return array(
			array(ArgvInput::class, true, array('bin/console', 'boot', 'help')),
			array(ArgvInput::class, false, array('bin/console', 'help')),
			array(ArrayInput::class, true, array('kernel' => 'boot', 'command' => 'help')),
			array(ArrayInput::class, false, array('command' => 'help')),
			array(StringInput::class, true, 'boot help'),
			array(StringInput::class, false, 'help'),
		);
	}
	
	/**
	 * @covers ::removeKernelArgument()
	 * @dataProvider provide_testRemoveKernelArgument_data
	 * @param string $inputClassName Input class to test
	 * @param boolean $shift Remove the first non-option token from input
	 * @param mixed ...$ctorArgs Constructor parameters for the input
	 */
	public function testRemoveKernelArgument($inputClassName, $shift, ...$ctorArgs)
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$class = new \ReflectionClass($inputClassName);
		$input = $class->newInstanceArgs($ctorArgs);
		
		try {
			$input->bind(self::$application->getDefinition());
		} catch (\Exception $e) {
			
		}
		
		$this->callMethod(self::$application, 'removeKernelArgument', $input, $shift);

		$this->assertEquals(array('command'), array_keys(self::$application->getDefinition()->getArguments()));
		
		$this->assertEquals(array('command' => 'help'), $input->getArguments());
	}
	
	/**
	 * @covers ::getApplicationCommands()
	 */
	public function testGetApplicationCommands()
	{
		$expected = array(
			'about' => 'boot:app',
			'help' => 'boot:app',
			'list' => 'boot:app',

			'assets:install' => 'boot:app',
			
			'cache:clear' => 'boot:app',
			'cache:pool:clear' => 'boot:app',
			'cache:warmup' => 'boot:app',
			
			'config:dump-reference' => 'boot:app',
			
			'debug:config' => 'boot:app',
			'debug:container' => 'boot:app',
			'debug:event-dispatcher' => 'boot:app',
			'debug:translation' => 'boot:app',
			
			'translation:update' => 'boot:app',
		);
		
		$application = $this->callMethod(self::$application, 'getApplication', 'boot');
		$application->add($application->get('help')->setAliases(array('help:help')));
		
		$map = array_map(function($a) {
			return implode(':', array_keys($a));
		}, $this->callMethod(self::$application, 'getApplicationCommands'));
		
		$this->assertEquals($expected, $map);
	}
	
	/**
	 * @covers ::hideCommands()
	 */
	public function testHideCommands()
	{
		$commands = array(
			'boot' => new HelpCommand(),
			'app' => new HelpCommand(),
		);
		
		$this->callMethod(self::$application, 'hideCommands', $commands);
		
		foreach ($commands as $command) {
			$this->assertTrue($command->isHidden());
		}
	}
	
	/**
	 * @covers ::hideCommands()
	 * @depends testHideCommands
	 */
	public function testHideCommandsWithName()
	{
		$commands = array(
			'boot' => new HelpCommand(),
			'app' => new HelpCommand(),
		);
		
		$globalCommand = self::$application->get('help');
		
		$this->callMethod(self::$application, 'hideCommands', $commands, 'help');
		
		foreach ($commands as $command) {
			$this->assertTrue($command->isHidden());
		}
		
		$this->assertTrue($globalCommand->isHidden());
	}
	
	/**
	 * @covers ::registerCommands()
	 * @depends testGetApplicationCommands
	 * @depends testHideCommandsWithName
	 */
	public function testRegisterCommands()
	{
		// Check that commands are registered
		$this->assertAttributeEquals(true, 'commandsRegistered', self::$application);
		
		// Check the help command is an instance of the correct class
		$this->assertInstanceOf(HelpCommand::class, self::$application->find('help'));
		
		// Check the list command is an instance of the correct class
		$this->assertInstanceOf(ListCommand::class, self::$application->find('list'));
	}
	
	/**
	 * @covers ::registerCommands()
	 * @depends testRegisterCommands
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "debug:config" does not exist.
	 */
	public function testRegisterCommandsHidesCommands()
	{
		$container = self::$application->getKernel()->getContainer();
		
		$parameters = array_merge($this->getObjectAttribute($container, 'parameters'), array(
			'motana.multikernel.commands.hidden' => array(
				'help',
				'list',
				'debug:config',
			),
		));
		$this->writeAttribute($container, 'parameters', $parameters);
		
		$this->writeAttribute(self::$application, 'commandsRegistered', false);
		$this->callMethod(self::$application, 'registerCommands');
		
		// Check that the help and list commands are available
		self::$application->get('help');
		self::$application->get('list');
		
		// Check that the debug:config command is hidden
		self::$application->get('debug:config');
	}
	
	/**
	 * Data provider for testRegisteredCommands().
	 *
	 * @return array
	 */
	public function provide_testRegisteredCommands_data()
	{
		$serverCommandBundle = class_exists('Symfony\\Bundle\\WebServerBundle\\WebServerBundle') ? 'WebServerBundle' : 'FrameworkBundle';
		
		return array(
			// Standard symfony commands that are always run on the boot kernel
			array(HelpCommand::class, 'help'),
			array(ListCommand::class, 'list'),
			array(YamlLintCommand::class, 'lint:yaml'),
			array('Symfony\\Bundle\\' . $serverCommandBundle . '\\Command\\ServerRunCommand', 'server:run'),
			array('Symfony\\Bundle\\' . $serverCommandBundle . '\\Command\\ServerStartCommand', 'server:start'),
			array('Symfony\\Bundle\\' . $serverCommandBundle . '\\Command\\ServerStatusCommand', 'server:status'),
			array('Symfony\\Bundle\\' . $serverCommandBundle . '\\Command\\ServerStopCommand', 'server:stop'),
			
			// Standard Symfony commands wrapped into multi-kernel command instances
			array(MultiKernelCommand::class, 'assets:install'),
			array(MultiKernelCommand::class, 'cache:clear'),
			array(MultiKernelCommand::class, 'cache:pool:clear'),
			array(MultiKernelCommand::class, 'cache:warmup'),
			array(MultiKernelCommand::class, 'config:dump-reference'),
			array(MultiKernelCommand::class, 'debug:config'),
			array(MultiKernelCommand::class, 'debug:container'),
			array(MultiKernelCommand::class, 'debug:event-dispatcher'),
			array(MultiKernelCommand::class, 'debug:translation'),
			array(MultiKernelCommand::class, 'translation:update'),
			
			// Multi-kernel bundle commands
			array(MultiKernelCreateAppCommand::class, 'multikernel:create-app'),
		);
	}
	
	/**
	 * @covers ::registerCommands
	 * @dataProvider provide_testRegisteredCommands_data
	 * @param string $expectedClassName Expected command class name
	 * @param string $command Command name
	 */
	public function testRegisteredCommands($expectedClassName, $command)
	{
		$this->assertInstanceOf($expectedClassName, self::$application->get($command));
	}
	
	/**
	 * @covers ::getDefaultInputDefinition()
	 */
	public function testGetDefaultInputDefinition()
	{
		$definition = $this->callMethod(self::$application, 'getDefaultInputDefinition');
		/** @var InputDefinition $definition */
		
		// Check the return value is an instance of the correct class
		$this->assertInstanceOf(InputDefinition::class, $definition);
		
		$arguments = array_values($definition->getArguments());
		$options = array_values($definition->getOptions());
		
		// Check the arguments are correct
		$this->assertEquals(2, count($arguments));
		$this->assertInputArgument($arguments[0], KernelArgument::class, 'kernel', InputArgument::REQUIRED, 'The kernel to execute');
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
	 * @depends testGetDefaultInputDefinition
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage A MultikernelApplication requires a Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput to work
	 */
	public function testDoRunChecksInputType()
	{
		self::$application->doRun($this->createMock(InputInterface::class), new BufferedOutput());
	}

	/**
	 * @covers ::doRun()
	 * @covers ::doRunSingleKernel()
	 * @depends testDoRunChecksInputType
	 */
	public function testDoRunSingleKernel()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'boot', 'debug:config');
		
		self::$application->doRun($input = new ArgvInput(), $output = new BufferedOutput());
		
		$content = $output->fetch();
		
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertNotContains('Executing command on kernel app...', $content);
	}

	/**
	 * @covers ::doRun()
	 * @covers ::doRunMultiKernel()
	 * @depends testDoRunSingleKernel
	 */
	public function testDoRunMultiKernel()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'debug:config');
		
		self::$application->doRun($input = new ArgvInput(), $output = new BufferedOutput());

		$content = $output->fetch();
		
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertContains('Executing command on kernel app...', $content);
	}
	
	/**
	 * @covers ::run()
	 * @depends testDoRunMultiKernel
	 */
	public function testRunSingleKernel()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'boot', 'debug:config');
		
		self::$application->setAutoExit(false);
		self::$application->run($input = new ArgvInput(), $output = new BufferedOutput());
		
		$content = $output->fetch();
		
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertNotContains('Executing command on kernel app...', $content);
	}
	
	/**
	 * @covers ::run()
	 * @depends testRunSingleKernel
	 */
	public function testRunMultiKernel()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'debug:config');

		self::$application->setAutoExit(false);
		self::$application->run($input = new ArgvInput(), $output = new BufferedOutput());
		
		$content = $output->fetch();
		
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertContains('Executing command on kernel app...', $content);
	}
	
	/**
	 * @covers ::run()
	 * @depends testRunMultiKernel
	 */
	public function testRunCreatesInput()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'boot', 'debug:config');
		
		self::$application->setAutoExit(false);
		self::$application->run(null, $output = new BufferedOutput());
		
		$content = $output->fetch();
		
		$this->assertContains('Executing command on kernel boot...', $content);
	}
}
