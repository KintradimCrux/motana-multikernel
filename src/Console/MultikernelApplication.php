<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console;

use Motana\Bundle\MultikernelBundle\Command\MultikernelCommand;
use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Console\Input\ConditionalKernelArgument;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Multi-Kernel Application.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultikernelApplication extends Application
{
	/**
	 * Application instances.
	 *
	 * @var Application[]
	 */
	private $applications = [];
	
	/**
	 * Boolean indicating that commands are registered.
	 *
	 * @var boolean
	 */
	private $commandsRegistered = false;
	
	/**
	 * Constructor.
	 *
	 * @param KernelInterface $kernel A KernelInterface instance
	 */
	public function __construct(KernelInterface $kernel)
	{
		// Booting the kernel is required for checking available bundles
		$kernel->boot();
		
		// Check the multi-kernel bundle is loaded
		$kernel->getBundle('MotanaMultikernelBundle');
		
		parent::__construct($kernel, true);
	}
	
	/**
	 * Returns the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		return new InputDefinition([
			new ConditionalKernelArgument('kernel', InputArgument::REQUIRED, 'The kernel to execute', array_keys($this->getKernel()->getKernels())),
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
			
			new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
			new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
			new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
			new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
			new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
			new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
		]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::registerCommands()
	 */
	protected function registerCommands()
	{
		// Commands are already registered, do nothing
		if ($this->commandsRegistered) {
			return;
		}
		
		// Commands are registered now
		$this->commandsRegistered = true;
		
		// Let the symfony core register commands
		parent::registerCommands();
		
		// Get the container
		$container = $this->getKernel()->getContainer();
		
		// Get the number of applications
		$kernelCount = count($this->getApplications());
		
		// Get parameters from the bundle configuration
		$forceMultiKernel = array_flip($container->getParameter('motana.multikernel.commands.add'));
		$forceGlobal = array_flip($container->getParameter('motana.multikernel.commands.global'));
		$forceHidden = array_flip($container->getParameter('motana.multikernel.commands.hidden'));
		$ignore = array_flip($container->getParameter('motana.multikernel.commands.ignore'));
		
		// Process all found commands
		$clonedApps = [];
		foreach ($this->getApplicationCommands() as $commandName => $commandList)
		{
			// Skip ignored commands
			if (isset($ignore[$commandName])) {
				continue;
			}
			
			// Hide commands configured to be hidden
			if (isset($forceHidden[$commandName])) {
				$this->hideCommands($commandList, $commandName);
			}
			
			// Add configured commands as global commands
			elseif (isset($forceGlobal[$commandName]))
			{
				// There is no cloned app with that name
				$app = key($commandList);
				if ( ! isset($clonedApps[$app]))
				{
					// Get the name of the kernel and application classes from the command
					$appClass = get_class(current($commandList)->getApplication());
					$kernelClass = get_class(current($commandList)->getApplication()->getKernel());
					
					// Create new kernel and application instances
					$kernel = new $kernelClass($container->getParameter('kernel.environment'), $container->getParameter('kernel.debug'));
					$clonedApps[$app] = new $appClass($kernel);
				}
				
				// Get the command from the application
				$command = $clonedApps[$app]->get($commandName);
				
				// Set the container of container aware commands
				if ($command instanceof ContainerAwareInterface) {
					$command->setContainer($container);
				}
				
				// Add the command
				$this->add($command);
				
				// Hide the original commands
				$this->hideCommands($commandList);
			}
			
			// All remaining commands are either ignored, or added as multi-kernel
			// commands, if available for all kernels or forced by configuration
			elseif ((isset($forceMultiKernel[$commandName]) || $kernelCount === count($commandList))) {
				$this->add(new MultikernelCommand($commandName, $commandList));
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::run()
	 */
	public function run(InputInterface $input = null, OutputInterface $output = null)
	{
		// Create an input if required
		if ( ! $input instanceof ArgvInput) {
			$input = new ArgvInput();
		}
		
		// Run the application
		return parent::run($input, $output);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::doRun()
	 */
	public function doRun(InputInterface $input, OutputInterface $output)
	{
		// Check the input is an instance of the correct class
		if ( ! $input instanceof ArgvInput) {
			throw new \InvalidArgumentException(sprintf('A MultikernelApplication requires a %s to work', ArgvInput::class));
		}
		
		// Bind input to the application definition
		try {
			$input->bind($this->getDefinition());
		} catch (\Throwable $e) {
		} catch (\Exception $e) {
		}
		
		// A kernel name was specified, run a command for a single kernel
		if (null !== $kernelName = $input->getArgument('kernel')) {
			return $this->doRunSingleKernel($kernelName, $input, $output);
		}
		
		// No kernel name was specified, run a global or multi-kernel command
		return $this->doRunMultiKernel($input, $output);
	}

	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Console\Application::renderException()
	 */
	public function renderException(\Exception $e, OutputInterface $output)
	{
		// Get the running command
		$command = $this->runningCommand;
		
		// Clear the running command if running a multi-kernel command
		if ($command instanceof MultikernelCommand) {
			$this->runningCommand = null;
		}
		
		// Render the exception
		parent::renderException($e, $output);
		
		// Runninng a multi-kernel command
		if ($command instanceof MultikernelCommand)
		{
			// Generate a prefix for the synopsis
			$prefix = $this->makePathRelative($_SERVER['PHP_SELF']);
			if ($command->getKernelName()) {
				$prefix .= ' ' . $command->getKernelName();
			}
			
			// Output the synopsis
			$synopsis = str_replace(' <command>', '', $command->getSynopsis());
			$output->writeln(sprintf('<info>%s %s</info>', $prefix, $synopsis), OutputInterface::VERBOSITY_QUIET);
			$output->writeln('', OutputInterface::VERBOSITY_QUIET);
			
			// Restore the running command
			$this->runningCommand = $command;
		}
	}
	
	/**
	 * Run a command on multiple kernels.
	 *
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 * @return integer
	 */
	private function doRunMultiKernel(InputInterface $input, OutputInterface $output)
	{
		// Remove the kernel argument
		$this->removeKernelArgument($input, false);
		
		// Get the container
		$container = $this->getKernel()->getContainer();
		
		// Update the container of all commands
		foreach ($this->all() as $command) {
			if ($command instanceof ContainerAwareInterface) {
				$command->setContainer($container);
			}
		}
		
		// Run the command
		return parent::doRun($input, $output);
	}
	
	/**
	 * Run a command on a single kernel.
	 *
	 * @param string $kernelName Kernel name
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 * @return integer
	 */
	private function doRunSingleKernel($kernelName, InputInterface $input, OutputInterface $output)
	{
		// Remove the kernel argument
		$this->removeKernelArgument($input);
		
		// Output a message with the kernel name if the command is not help or list
		if ((null !== $command = $input->getArgument('command'))
		&& ! in_array($command, [ 'help', 'list' ])
		&& ! $input->hasParameterOption([ '--help', '-h', '--version', '-V' ])) {
			$output->writeln(sprintf('Executing command on kernel <comment>%s</comment>...', $kernelName));
		}
		
		// Let the application run the command
		return $this->getApplication($kernelName)
			->doRun($input, $output);
	}
	
	/**
	 * Returns the application instances for all Kernels.
	 *
	 * @return Application[]
	 */
	public function getApplications()
	{
		// Return existing instances if available
		if ( ! empty($this->applications)) {
			return $this->applications;
		}
		
		// Create the instance for each application
		foreach ($this->getKernel()->getKernels() as $kernelName => $kernel)
		{
			// Create a second instance of the boot kernel for the application
			if ('boot' === $kernelName) {
				$class = get_class($kernel);
				$kernel = new $class($this->getKernel()->getEnvironment(), $this->getKernel()->isDebug());
			}
			
			// Add the application instance
			$this->applications[$kernelName] = new Application($kernel, false);
		}
		
		// Return the instances
		return $this->applications;
	}
	
	/**
	 * Returns the application instance for a kernel.
	 *
	 * @param string $kernelName A kernel name
	 * @return Application
	 */
	public function getApplication($kernelName)
	{
		// Fill the applications list if required
		if (empty($this->applications)) {
			$this->getApplications();
		}
		
		// Return the requested application if it exists
		return isset($this->applications[$kernelName]) ? $this->applications[$kernelName] : null;
	}
	
	/**
	 * Returns an array of available commands from all applications.
	 * Command aliases are filtered out.
	 *
	 * @return array
	 */
	private function getApplicationCommands()
	{
		// Process commands of all applications
		$commands = [];
		foreach ($this->getApplications() as $kernelName => $application) {
			/** @var Application $application */
			foreach ($application->all() as $commandName => $command)
			{
				// Skip command aliases
				if ($commandName !== $command->getName()) {
					continue;
				}
				
				// Add command to list
				$commands[$commandName][$kernelName] = $command;
			}
		}
		
		// Return the commands
		return $commands;
	}
	
	/**
	 * Sets an array of commands to hidden.
	 *
	 * @param Command[] $commands Commands to hide
	 */
	private function hideCommands(array $commands, $commandName = null)
	{
		// Hide all commands in the specified array
		foreach ($commands as $command) {
			$command->setHidden(true);
		}
		
		// Command name specified, hide the global command
		if (null !== $commandName) {
			try {
				$this->get($commandName)->setHidden(true);
			} catch (\Throwable $e) {
			} catch (\Exception $e) {
			}
		}
	}
	
	/**
	 * Removes the kernel argument from the input definition.
	 *
	 * @return void
	 */
	private function removeKernelArgument(InputInterface $input, $shift = true)
	{
		// Remove kernel argument from definition
		if ($this->getDefinition()->hasArgument('kernel')) {
			$this->getDefinition()->setArguments(
				array_filter(
					$this->getDefinition()->getArguments(),
					function(InputArgument $argument, $argumentName) {
						return 'kernel' !== $argumentName;
					}, ARRAY_FILTER_USE_BOTH
				)
			);
		}
		
		// Remove the first argument from commandline
		if ($shift)
		{
			// Process argv input
			if ($input instanceof ArgvInput) {
				$input->shift();
			}
			
			// Process array input
			elseif ($input instanceof ArrayInput) {
				$input->__construct(array_merge(
					array_filter(
						$input->getArguments(),
						function($argumentValue, $argumentName) {
							return 'kernel' !== $argumentName;
						},
						ARRAY_FILTER_USE_BOTH
					),
					$input->getOptions()
				));
			}
			
			// Process string input
			elseif ($input instanceof StringInput) {
				$args = $input->getArguments();
				$input->__construct(str_replace(
					[
						$args['kernel'],
						$args['kernel'] . ' ',
					],
					'',
					(string) $input
				));
			}
		}
		
		// Bind input to the definition, catch any exception thrown
		try {
			$input->bind($this->getDefinition());
		} catch (\Throwable $e) {
		} catch (\Exception $e) {
		}
	}
}
