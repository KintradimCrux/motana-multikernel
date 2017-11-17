<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * A multi-kernel command executes a command on the application instances
 * for one or more kernels.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultikernelCommand extends ContainerAwareCommand
{
	/**
	 * Wrapped commands.
	 *
	 * @var Command[]
	 */
	private $commands;
	
	/**
	 * Kernel name currently executing a command for.
	 *
	 * @var string
	 */
	private $kernelName;
	
	/**
	 * Constructor.
	 *
	 * @param string $name Command name
	 * @param Command[] $commands An array of Command instances
	 */
	public function __construct($name, array $commands = [])
	{
		$this->commands = $commands;
		
		parent::__construct($name);
	}
	
	/**
	 * Returns the command for a kernel name.
	 *
	 * @param string $kernelName Kernel name
	 * @return NULL|\Symfony\Component\Console\Command\Command
	 */
	public function getCommandForKernel($kernelName)
	{
		return isset($this->commands[$kernelName]) ? $this->commands[$kernelName] : null;
	}
	
	/**
	 * Returns the kernel name currently executing a command for.
	 * This is used in error processing.
	 *
	 * @return string
	 */
	public function getKernelName()
	{
		return $this->kernelName;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		return in_array(true, array_map(
			function(Command $c) {
				return $c->isEnabled();
			},
			$this->commands
		));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isHidden()
	 */
	public function isHidden()
	{
		return ! in_array(false, array_map(
			function(Command $c) {
				return $c->isHidden();
			},
			$this->commands
		));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
	{
		$command = reset($this->commands);
		/** @var Command $command */
		
		$this->setAliases((array) $command->getAliases())
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
			if ( ! $command->isEnabled()
				|| $command->isHidden()) {
				if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
					$output->writeln(sprintf('Skipping command on kernel <comment>%s</comment> (command disabled)', $kernelName));
				}
				
				continue;
			}
			
			$this->kernelName = $kernelName;
			
			$output->writeln(sprintf('Executing command on kernel <comment>%s</comment>...', $kernelName));
			
			$command->getApplication()->doRun($input, $output);
			
			$command->getApplication()->getKernel()->shutdown();
		}
		
		$this->kernelName = null;
	}
}
