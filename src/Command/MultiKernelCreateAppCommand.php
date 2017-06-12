<?php

namespace Motana\Bundle\MultiKernelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MultiKernelCreateAppCommand extends Command
{
	protected function configure() {
		$this->setName('multi-kernel:create-app')
		->setDescription('Create a new app');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		
	}
}
