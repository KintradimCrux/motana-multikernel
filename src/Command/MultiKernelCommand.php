<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * A multi-kernel command executes a command on the application instances
 * for one or more kernels.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultiKernelCommand extends ContainerAwareCommand
{
	// {{{ Properties
	
	/**
	 * Wrapped commands.
	 *
	 * @var Command[]
	 */
	private $commands;
	
	// }}}
	// {{{ Constructor
	
	/**
	 * Constructor.
	 *
	 * @param string $name Command name
	 * @param Command[] $commands An array of Command instances
	 */
	public function __construct($name, array $commands = array())
	{
		$this->commands = $commands;
		
		parent::__construct($name);
	}
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		return in_array(true, array_map(function(Command $c){
			return $c->isEnabled();
		}, $this->commands));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isHidden()
	 */
	public function isHidden()
	{
		return ! in_array(false, array_map(function(Command $c){
			return $c->isHidden();
		}, $this->commands));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
	{
		$command = reset($this->commands);
		/** @var Command $command */
		
		$this->setAliases($command->getAliases())
		->setDefinition(clone($command->getNativeDefinition()))
		->setDescription($command->getDescription())
		->setHelp($command->getHelp());
		
		foreach ($command->getUsages() as $usage) {
			$this->addUsage($usage);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		foreach ($this->commands as $kernelName => $command) {
			if ( ! $command->isEnabled()) {
				if (OutputInterface::VERBOSITY_VERBOSE) {
					$output->writeln(sprintf('Skipping command on kernel <comment>%s</comment> (command disabled)', $kernelName));
				}
				
				continue;
			}
			
			$output->writeln(sprintf('Executing command on kernel <comment>%s</comment>...', $kernelName));
			
			try {
				$command->getApplication()->doRun($input, $output);
			}
			catch (\Exception $e) {
				if ($output instanceof ConsoleOutputInterface) {
					$command->getApplication()->renderException($e, $output->getErrorOutput());
				} else {
					$command->getApplication()->renderException($e, $output);
				}
			}
			
			if ('boot' !== $kernelName) {
				$command->getApplication()->getKernel()->shutdown();
			}
		}
	}
	
	// }}}
}
