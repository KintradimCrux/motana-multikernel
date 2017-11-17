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

use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * A replacement for the Symfony Standard Edition list command.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ListCommand extends SymfonyListCommand implements ContainerAwareInterface
{
	use ContainerAwareTrait;
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\ListCommand::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = new DescriptorHelper();
		$helper->setContainer($this->container);
		$helper->describe($output, $this->getApplication(), [
			'format' => $input->getOption('format'),
			'raw_text' => $input->getOption('raw'),
			'namespace' => $input->getArgument('namespace'),
		]);
	}
}
