<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Motana\Bundle\MultikernelBundle\Console\Helper\DescriptorHelper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand as SymfonyHelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * A replacement for the Symfony Standard Edition help command.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class HelpCommand extends SymfonyHelpCommand implements ContainerAwareInterface
{
	use ContainerAwareTrait;
	
	/**
	 * The command to show help for.
	 *
	 * @var Command
	 */
	private $command;
	
	/**
	 * Sets the command.
	 *
	 * @param Command $command The command to set
	 */
	public function setCommand(Command $command)
	{
		$this->command = $command;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\HelpCommand::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (null === $this->command) {
			$this->command = $this->getApplication()->find($input->getArgument('command_name'));
		}
		
		$helper = new DescriptorHelper();
		$helper->setContainer($this->container);
		$helper->describe($output, $this->command, [
			'format' => $input->getOption('format'),
			'raw_text' => $input->getOption('raw'),
		]);
		
		$this->command = null;
	}
}
