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

use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Motana\Bundle\MultiKernelBundle\Console\Helper\DescriptorHelper;

/**
 * A replacement for the Symfony Standard Edition list command.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ListCommand extends SymfonyListCommand
{
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\ListCommand::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = new DescriptorHelper();
		$helper->describe($output, $this->getApplication(), array(
			'format' => $input->getOption('format'),
			'raw_text' => $input->getOption('raw'),
			'namespace' => $input->getArgument('namespace'),
		));
	}
	
	// }}}
}
