<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

use Motana\Bundle\MultiKernelBundle\Command\MultiKernelCommand;
use Motana\Bundle\MultiKernelBundle\Console\Input\ArgvInput;
use Motana\Bundle\MultiKernelBundle\Console\Input\KernelArgument;

/**
 * Multi-Kernel Application.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultiKernelApplication extends Application
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
		parent::__construct($kernel, true);
	}
	
	// }}}
	// {{{ Method overrides
	
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
			throw new \InvalidArgumentException(sprintf('A MultiKernelApplication requires a %s to work', ArgvInput::class));
		}
		
		try { $input->bind($this->getDefinition()); }
		catch (\Exception $e) { }
		
		if (null !== $kernelName = $input->getArgument('kernel')) {
			/** @var ArgvInput $input */
			$input->shift();

			if ((null !== $command = $input->getArgument('command')) && ! in_array($command, ['help', 'list'])) {
				$output->writeln(sprintf('Executing command on kernel <comment>%s</comment>...', $kernelName));
			}
			
			return $this->getApplication($kernelName)
				->doRun($input, $output);
		}

		$container = $this->getKernel()->getContainer();
		foreach ($this->all() as $command) {
			if ($command instanceof ContainerAwareInterface) {
				$command->setContainer($container);
			}
		}
		
		return parent::doRun($input, $output);
	}
	
	/**
	 * Gets the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		return new InputDefinition(array(
			new KernelArgument('kernel', InputArgument::OPTIONAL, 'The kernel to execute', array_keys($this->getKernel()->getKernels())),
			new InputArgument('command', InputArgument::OPTIONAL, 'The command to execute'),
			
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
		
		$commands = array();
		
		foreach ($this->getApplications() as $applicationName => $application) {
			/** @var Application $application */
			foreach ($application->all() as $commandName => $command) {
				if ($command->getName() === $commandName) {
					$commands[$commandName][$applicationName] = $command;
				}
			}
		}
		
		$count = count($this->getApplications());
		foreach ($commands as $commandName => $commandList) {
			switch ($commandName) {
				case 'help':
				case 'list':
					break;
					
				case 'lint:yaml':
				case 'security:check':
				case 'server:run':
				case 'server:start':
				case 'server:status':
				case 'server:stop':
					$this->add(reset($commandList));
					break;
				
				default:
					if ($count === count($commandList)) {
						$this->add(new MultiKernelCommand($commandName, $commandList));
					}
					break;
			}
		}
	}
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Returns the application instances for all Kernels.
	 *
	 * @return Application[]
	 */
	private function getApplications()
	{
		if (empty($this->applications)) {
			foreach ($this->getKernel()->getKernels() as $applicationName => $kernel) {
				$this->applications[$applicationName] = $application = new Application($kernel, false);
			}
		}
		
		return $this->applications;
	}
	
	/**
	 * Returns the application instance for a kernel.
	 *
	 * @param string $applicationName An application name
	 * @return Application
	 */
	private function getApplication($applicationName)
	{
		return isset($this->applications[$applicationName]) ? $this->applications[$applicationName] : null;
	}
	
	// }}}
}
