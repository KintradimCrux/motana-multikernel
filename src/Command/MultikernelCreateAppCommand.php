<?php

/**
 * need more of these:
 *
 * multikernel:convert-project
 * multikernel:clone-app
 * multikernel:create-app
 * multikernel:remove-app
 * multikernel:reset-config
 */


namespace Motana\Bundle\MultikernelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MultikernelCreateAppCommand extends Command
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		return 'boot' === $this->getApplication()->getKernel()->getName();
	}
	
	protected function configure() {
		$this->setName('multikernel:create-app')
		->setDescription('Create a new app');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		
	}
}
