<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Console;

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

use Motana\Bundle\MultikernelBundle\Command\MultikernelCommand;
use Motana\Bundle\MultikernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultikernelBundle\Console\Input\KernelArgument;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;

/**
 * Multi-Kernel Application.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultikernelApplication extends Application
{
	// {{{ Properties
	
	/**
	 * Application instances.
	 *
	 * @var Application[]
	 */
	private $applications = array();
	
	/**
	 * Boolean indicating that commands are registered.
	 *
	 * @var boolean
	 */
	private $commandsRegistered = false;
	
	// }}}
	// {{{ Constructor
	
	/**
	 * Constructor.
	 *
	 * @param KernelInterface $kernel A KernelInterface instance
	 */
	public function __construct(KernelInterface $kernel)
	{
		$kernel->boot();
		$kernel->getBundle('MotanaMultikernelBundle');
		
		parent::__construct($kernel, true);
	}
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * Returns the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		return new InputDefinition(array(
			new KernelArgument('kernel', InputArgument::REQUIRED, 'The kernel to execute', array_keys($this->getKernel()->getKernels())),
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
			
			new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
			new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
			new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
			new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
			new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
			new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
		));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::registerCommands()
	 */
	protected function registerCommands()
	{
		if ($this->commandsRegistered) {
			return;
		}
		
		$this->commandsRegistered = true;
		
		parent::registerCommands();
		
		$container = $this->getKernel()->getContainer();
		
		$kernelCount = count($this->getApplications());
		
		$forceMultiKernel = array_flip($container->getParameter('motana.multikernel.commands.add'));
		$forceGlobal = array_flip($container->getParameter('motana.multikernel.commands.global'));
		$forceHidden = array_flip($container->getParameter('motana.multikernel.commands.hidden'));
		
		foreach ($this->getApplicationCommands() as $commandName => $commandList) {
			if (in_array($commandName, ['help', 'list'])) {
				continue;
			}
			
			if (isset($forceHidden[$commandName])) {
				$this->hideCommands($commandList, $commandName);
			} elseif (isset($forceGlobal[$commandName])) {
				$this->add(clone(current($commandList)));
				$this->hideCommands($commandList);
			} elseif ((isset($forceMultiKernel[$commandName]) || $kernelCount === count($commandList))) {
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
		if ( ! $input instanceof ArgvInput) {
			$input = new ArgvInput();
		}
		
		return parent::run($input, $output);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::doRun()
	 */
	public function doRun(InputInterface $input, OutputInterface $output)
	{
		if ( ! $input instanceof ArgvInput) {
			throw new \InvalidArgumentException(sprintf('A MultikernelApplication requires a %s to work', ArgvInput::class));
		}
		
		try {
			$input->bind($this->getDefinition());
		} catch (\Exception $e) {
			
		}
		
		if (null !== $kernelName = $input->getArgument('kernel')) {
			return $this->doRunSingleKernel($kernelName, $input, $output);
		}
		
		return $this->doRunMultiKernel($input, $output);
	}
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Run a command on multiple kernels.
	 * 
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 * @return integer
	 */
	private function doRunMultiKernel(InputInterface $input, OutputInterface $output)
	{
		$this->removeKernelArgument($input, false);
		
		$container = $this->getKernel()->getContainer();
		foreach ($this->all() as $command) {
			if ($command instanceof ContainerAwareInterface) {
				$command->setContainer($container);
			}
		}
		
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
		$this->removeKernelArgument($input);
		
		if ((null !== $command = $input->getArgument('command')) && ! in_array($command, ['help', 'list'])) {
			$output->writeln(sprintf('Executing command on kernel <comment>%s</comment>...', $kernelName));
		}
		
		return $this->getApplication($kernelName)
			->doRun($input, $output);
	}
	
	/**
	 * Returns the application instances for all Kernels.
	 *
	 * @return Application[]
	 */
	private function getApplications()
	{
		if ( ! empty($this->applications)) {
			return $this->applications;
		}
		
		foreach ($this->getKernel()->getKernels() as $kernelName => $kernel) {
			$this->applications[$kernelName] = new Application($kernel, false);
		}
		
		return $this->applications;
	}
	
	/**
	 * Returns the application instance for a kernel.
	 *
	 * @param string $kernelName A kernel name
	 * @return Application
	 */
	private function getApplication($kernelName)
	{
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
		$commands = array();
		
		foreach ($this->getApplications() as $kernelName => $application) {
			/** @var Application $application */
			foreach ($application->all() as $commandName => $command) {
				if ($commandName !== $command->getName()) {
					continue;
				}
				
				$commands[$commandName][$kernelName] = $command;
			}
		}
		
		return $commands;
	}
	
	/**
	 * Sets an array of commands to hidden. 
	 * 
	 * @param Command[] $commands Commands to hide
	 */
	private function hideCommands(array $commands, $commandName = null)
	{
		foreach ($commands as $command) {
			$command->setHidden(true);
		}
		
		if (null !== $commandName) {
			try {
				$this->get($commandName)->setHidden(true);
			} catch (\Exception $e) { }
		}
	}
	
	/**
	 * Removes the kernel argument from the input definition.
	 *
	 * @return void
	 */
	private function removeKernelArgument(InputInterface $input, $shift = true)
	{
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
		
		if ($shift) {
			if ($input instanceof ArgvInput) {
				$input->shift();
			} elseif ($input instanceof ArrayInput) {
				$input->__construct(array_merge(
					array_filter(
						$input->getArguments(),
						function($argumentValue, $argumentName){
							return 'kernel' !== $argumentName; 
						}, ARRAY_FILTER_USE_BOTH
					),
					$input->getOptions()
				));
			} elseif ($input instanceof StringInput) {
				$input->__construct(ltrim(strstr((string) $input, ' ')));
			}
		}
		
		try {
			$input->bind($this->getDefinition());
		} catch (\Exception $e) {
			
		}
	}
	
	// }}}
}
