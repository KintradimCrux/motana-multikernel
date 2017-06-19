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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

use Motana\Bundle\MultikernelBundle\Command\MultikernelCommand;
use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Test\CommandTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\MultiKernelCommand
 */
class MultikernelCommandTest extends CommandTestCase
{
	/**
	 * Constructor.
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'debug:config', array('--format' => 'txt'));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
	}
	
	/**
	 * Adds a configured mock multikernel command including sub-commands.
	 * 
	 * @param string $commandName Command name
	 * @param array $configuration Mock configuration
	 * @return MultiKernelCommand
	 */
	protected function createConfiguredMultiKernelCommand($commandName, array $configuration = array())
	{
		$applications = $this->callMethod(self::$application, 'getApplications');
		
		$commands = array();
		
		foreach ($applications as $kernelName => $application) {
			$application->add($commands[$kernelName] = $this->createConfiguredCommand($commandName, array_merge($configuration, array(
				'isEnabled' => is_string($configuration['isEnabled']) ? $kernelName === $configuration['isEnabled'] : $configuration['isEnabled'],
			)), $application));
		}
		
		self::$application->add($command = new MultikernelCommand($commandName, $commands));
		
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
	protected function createConfiguredCommand($commandName, array $configuration = array(), Application $application)
	{
		$definition = new InputDefinition(array(
			new InputArgument('dummy', InputArgument::OPTIONAL, 'dummy argument'),
		));
		
		return $this->createConfiguredMock(Command::class, array_merge(array(
			'getAliases' => array(),
			'getApplication' => $application,
			'getDescription' => $commandName . ' command',
			'getHelp' => $commandName . ' command help',
			'getHelperSet' => $application->getHelperSet(),
			'getName' => $commandName,
			'getDefinition' => $definition,
			'getNativeDefinition' => $definition,
			'getUsages' => array(),
		), $configuration));
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertEquals($this->commandName, $this->readAttribute(self::$command, 'name'));
		$this->assertEquals(array('boot', 'app'), array_keys($this->readAttribute(self::$command, 'commands')));
	}
	
	/**
	 * @covers ::isEnabled()
	 */
	public function testIsEnabled()
	{
		$this->assertTrue(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @depends testIsEnabled
	 */
	public function testIsEnabledForOneKernel()
	{
		$command = $this->createConfiguredMultiKernelCommand('disabled', array(
			'isEnabled' => 'boot',
		));
		
		$this->assertTrue($command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @depends testIsEnabledForOneKernel
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "disabled" does not exist.
	 */
	public function testIsEnabledForNoKernel()
	{
		$command = $this->createConfiguredMultiKernelCommand('disabled', array(
			'isEnabled' => false,
		));
		
		$this->assertFalse($command->isEnabled());
		
		self::$application->get('disabled');
	}
	
	/**
	 * @covers ::isHidden()
	 */
	public function testIsHidden()
	{
		$this->assertFalse(self::$command->isHidden());
	}
	
	/**
	 * @covers ::isHidden()
	 * @depends testIsHidden
	 */
	public function testIsHiddenForOneKernel()
	{
		$commands = $this->readAttribute(self::$command, 'commands');
		$commands['boot']->setHidden(true);
		
		$this->assertFalse(self::$command->isHidden());
	}
	
	/**
	 * @covers ::isHidden()
	 * @depends testIsHiddenForOneKernel
	 */
	public function testIsHiddenForAllKernels()
	{
		$commands = $this->readAttribute(self::$command, 'commands');
		$commands['boot']->setHidden(true);
		$commands['app']->setHidden(true);
		
		$this->assertTrue(self::$command->isHidden());
	}
	
	/**
	 * @covers ::configure()
	 */
	public function testConfigure()
	{
		$command = $this->createConfiguredMultiKernelCommand('disabled', array(
			'isEnabled' => false,
			'getUsages' => array(
				'disabled [options]',
			)
		));
		
		$this->assertEquals(array(
			'name' => 'disabled',
			'aliases' => array(),
			'definition' => new InputDefinition(),
			'description' => 'disabled command',
			'help' => 'disabled command help',
			'usages' => array('disabled [options]'),
		), array(
			'name' => $command->getName(),
			'aliases' => $command->getAliases(),
			'definition' => new InputDefinition(),
			'description' => $command->getDescription(),
			'help' => $command->getHelp(),
			'usages' => $command->getUsages(),
		));
	}
	
	/**
	 * Data provider for testExecute().
	 * 
	 * @return array
	 */
	public function provide_testExecute_data()
	{
		return array(
			array('working', null, 'command_multikernel', array()),
			array('working', null, 'command_multikernel', array('--verbose' => Output::VERBOSITY_QUIET)),
			array('working', null, 'command_multikernel', array('--verbose' => Output::VERBOSITY_NORMAL)),
			array('working', null, 'command_multikernel', array('--verbose' => Output::VERBOSITY_VERBOSE)),
			array('working', null, 'command_multikernel', array('--verbose' => Output::VERBOSITY_VERY_VERBOSE)),
			array('working', null, 'command_multikernel', array('--verbose' => Output::VERBOSITY_DEBUG)),
			array('working', null, 'command_appkernel', array('kernel' => 'app')),
			array('working', null, 'command_appkernel', array('kernel' => 'app', '--verbose' => Output::VERBOSITY_QUIET)),
			array('working', null, 'command_appkernel', array('kernel' => 'app', '--verbose' => Output::VERBOSITY_NORMAL)),
			array('working', null, 'command_appkernel', array('kernel' => 'app', '--verbose' => Output::VERBOSITY_VERBOSE)),
			array('working', null, 'command_appkernel', array('kernel' => 'app', '--verbose' => Output::VERBOSITY_VERY_VERBOSE)),
			array('working', null, 'command_appkernel', array('kernel' => 'app', '--verbose' => Output::VERBOSITY_DEBUG)),
		);
	}

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
		$_SERVER['argv'] = array('bin/console', 'debug:config');
		
		if (isset($parameters['kernel'])) {
			array_splice($_SERVER['argv'], 1, 0, array($parameters['kernel']));
		}
		
		$this->setUp($type, $app);
		
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		$verbose = false;
		if (isset($parameters['--verbose'])) {
			self::$output->setVerbosity($parameters['--verbose']);
			$verbose = $parameters['--verbose'];
		}
		
		$parameters = array_merge(array('command' => $this->commandName), $this->commandParameters, $parameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		$options = $this->convertParametersToOptions($parameters);
		
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
	}
	
	/**
	 * Data provider for testExecute().
	 *
	 * @return array
	 */
	public function provide_testExecuteSkipsDisabledCommands_data()
	{
		return array(
			array('working', null, 'command_skipped', array()),
			array('working', null, 'command_skipped', array('--verbose' => Output::VERBOSITY_QUIET)),
			array('working', null, 'command_skipped', array('--verbose' => Output::VERBOSITY_NORMAL)),
			array('working', null, 'command_skipped', array('--verbose' => Output::VERBOSITY_VERBOSE)),
			array('working', null, 'command_skipped', array('--verbose' => Output::VERBOSITY_VERY_VERBOSE)),
			array('working', null, 'command_skipped', array('--verbose' => Output::VERBOSITY_DEBUG)),
		);
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_testExecuteSkipsDisabledCommands_data
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 */
	public function testExecuteSkipsDisabledCommands($type, $app, $template, array $parameters = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'disabled');
		
		$this->setUp($type, $app);

		$command = $this->createConfiguredMultiKernelCommand('disabled', array(
			'isEnabled' => 'boot',
		));
		
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		$verbose = false;
		if (isset($parameters['--verbose'])) {
			self::$output->setVerbosity($parameters['--verbose']);
			$verbose = $parameters['--verbose'];
		}
		
		$parameters = array_merge(array('command' => 'disabled'), $this->commandParameters, $parameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		$options = $this->convertParametersToOptions($parameters);
		
		$this->assertEquals($output = $this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
		
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
	 * @depends testExecute
	 */
	public function testExecuteExceptionHandling()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'debug:config', 'NotExistingBundle');
		
		$this->setUp('working');
		
		$command = $this->createConfiguredMultiKernelCommand('disabled', array(
			'isEnabled' => 'boot',
		));
		
		$parameters = array_merge(array('command' => 'disabled'), $this->commandParameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		self::$application->setAutoExit(false);
		self::$application->run($input, self::$output);
		
		$content = self::$output->fetch();
		
		$expectedException = '[LogicException]';
		$expectedExceptionMessage = 'No extension with alias "NotExistingBundle" is enabled.';
		
		$this->assertContains('Executing command on kernel boot...', $content);
		$this->assertContains('Executing command on kernel app...', $content);
		
		$this->assertContains($expectedException, $content);
		$this->assertEquals(2, substr_count($content, $expectedException));
		
		$this->assertContains($expectedExceptionMessage, $content);
		$this->assertEquals(2, substr_count($content, $expectedExceptionMessage));
	}
	
	/**
	 * @covers ::execute()
	 * @depends testExecute
	 */
	public function testExecuteExceptionHandlingWithConsoleOutput()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		$_SERVER['argv'] = array('bin/console', 'debug:config', 'NotExistingBundle');
		
		$this->setUp('working');
		
		$output = new ConsoleOutput(Output::VERBOSITY_QUIET, false);
		$output->setErrorOutput(self::$output);
		
		$command = $this->createConfiguredMultiKernelCommand('disabled', array(
			'isEnabled' => 'boot',
		));
		
		$parameters = array_merge(array('command' => 'disabled'), $this->commandParameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		self::$application->setAutoExit(false);
		self::$application->run($input, $output);
		
		$content = self::$output->fetch();
		
		$expectedException = '[LogicException]';
		$expectedExceptionMessage = 'No extension with alias "NotExistingBundle" is enabled.';
		
		$this->assertContains($expectedException, $content);
		$this->assertEquals(2, substr_count($content, $expectedException));
		
		$this->assertContains($expectedExceptionMessage, $content);
		$this->assertEquals(2, substr_count($content, $expectedExceptionMessage));
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Test\CommandTestCase::convertParametersToOptions()
	 */
	protected static function convertParametersToOptions(array $parameters = array())
	{
		$options = array();
		
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
		
		return $options;
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Test\CommandTestCase::filterCommandParameters()
	 */
	protected static function filterCommandParameters(array $parameters = array())
	{
		unset($parameters['--format']);
		
		return $parameters;
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Test\CommandTestCase::getTemplate()
	 */
	protected static function getTemplate($case, array $options = array(), $format, $commandName)
	{
		return parent::getTemplate($case, $options, $format, 'multikernel');
	}
}
