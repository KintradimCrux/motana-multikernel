<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console;

use Motana\Bundle\MultikernelBundle\Command\HelpCommand;
use Motana\Bundle\MultikernelBundle\Command\ListCommand;

use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base application for the Multi-Kernel extension of the Motana framework.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class Application extends BaseApplication
{
	/**
	 * Event dispatcher for the application.
	 *
	 * @var EventDispatcherInterface
	 */
	protected $dispatcher;
	
	/**
	 * Default command to execute when no command was specified.
	 *
	 * @var string
	 */
	protected $defaultCommand;
	
	/**
	 * The command currently running.
	 *
	 * @var Command
	 */
	protected $runningCommand;
	
	/**
	 * Boolean indicating to show help for a command.
	 *
	 * @var boolean
	 */
	protected $showHelp = false;
	
	/**
	 * Constructor.
	 *
	 * @param KernelInterface $kernel A KernelInterface instance
	 * @param boolean $autoExit Boolean indicating to enable the auto-exit feature (default: false)
	 */
	public function __construct(KernelInterface $kernel, $autoExit = false)
	{
		parent::__construct($kernel);
		
		$this->setAutoExit($autoExit);
		$this->defaultCommand = 'list';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::getName()
	 */
	public function getName()
	{
		return 'Motana Multi-Kernel App Console - Symfony';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::getDefaultCommands()
	 */
	protected function getDefaultCommands()
	{
		// Return the replacement help and list commands by default
		return [
			new HelpCommand(),
			new ListCommand(),
		];
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Bundle\FrameworkBundle\Console\Application::get()
	 */
	public function get($name)
	{
		// Get the command
		$command = parent::get($name);
		
		// Check the command exists, is enabled and is not hidden
		if (null === $command || ! $command->isEnabled() || $command->isHidden()) {
			throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name));
		}
		
		// Requested to show commandline help, return a help command for the requested command
		if ($this->showHelp) {
			$this->showHelp = false;
			
			$helpCommand = $this->get('help');
			$helpCommand->setCommand($command);
			
			return $helpCommand;
		}
		
		// Return the requested command
		return $command;
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::has()
	 */
	public function has($name)
	{
		// Command not available, return to caller
		if ( ! parent::has($name)) {
			return false;
		}
		
		// Get the command
		$command = parent::get($name);
		
		// Return a boolean indicating the command is enabled and not hidden
		return $command->isEnabled() && ! $command->isHidden();
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Bundle\FrameworkBundle\Console\Application::all()
	 */
	public function all($namespace = null)
	{
		// Get all commands
		$commands = parent::all($namespace);
		
		// Remove disabled and hidden commands from the list
		foreach ($commands as $name => $command) {
			if ( ! $command->isEnabled() || $command->isHidden()) {
				unset($commands[$name]);
			}
		}
		
		// Return the remaining commands
		return $commands;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::setDispatcher()
	 */
	public function setDispatcher(EventDispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;
		
		parent::setDispatcher($dispatcher);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::renderException()
	 */
	public function renderException(\Exception $e, OutputInterface $output)
	{
		// Render the exception
		parent::renderException($e, $output);
		
		// A running command is available
		if (null !== $this->runningCommand)
		{
			// Generate the prefix for the synopsis
			$prefix = $this->makePathRelative($_SERVER['PHP_SELF']) . ' ' . $this->getKernel()->getName();
			
			// Output the synopsis
			$synopsis = str_replace(' <command>', '', $this->runningCommand->getSynopsis());
			$output->writeln(sprintf('<info>%s %s</info>', $prefix, $synopsis), OutputInterface::VERBOSITY_QUIET);
			$output->writeln('', OutputInterface::VERBOSITY_QUIET);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Bundle\FrameworkBundle\Console\Application::doRun()
	 */
	public function doRun(InputInterface $input, OutputInterface $output)
	{
		// Get the container
		$this->getKernel()->boot();
		$container = $this->getKernel()->getContainer();
		
		// Set the container on all container aware commands
		foreach ($this->all() as $command) {
			if ($command instanceof ContainerAwareInterface) {
				$command->setContainer($container);
			}
		}
		
		// Set the event dispatcher
		$this->setDispatcher($container->get('event_dispatcher'));
		
		// Parameter --version is specified, output the application version
		if (true === $input->hasParameterOption([ '--version', '-V' ], true)) {
			$output->writeln($this->getLongVersion());
			
			return 0;
		}
		
		// Get the command name from input
		$name = $this->getCommandName($input);
		
		// Parameter --help was specified, show commandline help
		if (true === $input->hasParameterOption([ '--help', '-h' ], true))
		{
			// No command name specified, show the commandline help for the "help" command
			if ( ! $name) {
				$name = 'help';
				$input = new ArrayInput([ 'command_name' => $this->defaultCommand ]);
			}
			
			// A command name was specified, show the commandline help for it
			else {
				$this->showHelp = true;
			}
		}
		
		// No command was specified, use the default command
		if ( ! $name) {
			$name = $this->defaultCommand;
			$input = new ArrayInput([ 'command' => $this->defaultCommand ]);
		}
		
		// Get the command object
		try {
			$e = $this->runningCommand = null;
			// the command name MUST be the first element of the input
			$command = $this->find($name);
		} catch (\Throwable $e) {
		} catch (\Exception $e) {
		}
		
		// An exception was thrown while retrieving the command object
		if (null !== $e)
		{
			// An event dispatcher is available
			if (null !== $this->dispatcher)
			{
				// Handle the console error event
				$event = new ConsoleErrorEvent($input, $output, $e);
				$this->dispatcher->dispatch(ConsoleEvents::ERROR, $event);
				
				// Get the exit code from the event
				$e = $event->getError();
				if (0 === $event->getExitCode()) {
					return 0;
				}
			}
			
			// No event dispatcher available, re-throw the exception
			throw $e;
		}
		
		// Run the command
		$this->runningCommand = $command;
		$exitCode = $this->doRunCommand($command, $input, $output);
		$this->runningCommand = null;
		
		// Return the command exitcode
		return $exitCode;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::doRunCommand()
	 */
	protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
	{
		// Try running the command
		try {
			$e = null;
			$exitCode = parent::doRunCommand($command, $input, $output);
		} catch (\Throwable $e) {
		} catch (\Exception $e) {
		}
		
		// An exception was thrown while running the command
		if (null !== $e)
		{
			// Render the exception
			if ($output instanceof ConsoleOutputInterface) {
				$this->renderException($e, $output->getErrorOutput());
			} else {
				$this->renderException($e, $output);
			}
			
			// Get the exitcode from the exception
			$exitCode= $e->getCode();
			if (is_numeric($exitCode)) {
				$exitCode = (int) $exitCode;
				if (0 === $exitCode) {
					$exitCode = 1;
				}
			} else {
				$exitCode = 1;
			}
		}
		
		// Return the command exitcode
		return $exitCode;
	}
	
	/**
	 * Tries to make a path relative to the project, which prints nicer.
	 *
	 * @param string $absolutePath
	 * @return string
	 */
	protected function makePathRelative($absolutePath)
	{
		$projectRootDir = $this->getKernel()->getContainer()->getParameter('kernel.project_dir');
		
		return str_replace($projectRootDir, '.', realpath($absolutePath) ?: $absolutePath);
	}
}
