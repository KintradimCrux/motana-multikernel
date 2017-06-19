<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand as SymfonyHelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Motana\Bundle\MultikernelBundle\Console\Helper\DescriptorHelper;

/**
 * A replacement for the Symfony Standard Edition help command.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class HelpCommand extends SymfonyHelpCommand
{
	// {{{ Properties
	
	/**
	 * The command to show help for.
	 * 
	 * @var Command
	 */
	private $command;
	
	// }}}
	// {{{ Method overrides
	
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
		$helper->describe($output, $this->command, array(
			'format' => $input->getOption('format'),
			'raw_text' => $input->getOption('raw'),
		));
		
		$this->command = null;
	}
	
	// }}}
}
