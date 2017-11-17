<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Descriptor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\Descriptor as BaseDescriptor;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Extension for the Symfony Descriptor base class.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class Descriptor extends BaseDescriptor implements ContainerAwareInterface
{
	use ContainerAwareTrait;
	
	/**
	 * Returns the processed help for a command.
	 *
	 * @param Command $command Command to inspect
	 * @return string
	 */
	protected function getProcessedHelp(Command $command)
	{
		return rtrim(str_replace('php ' . $_SERVER['PHP_SELF'], $this->makePathRelative($_SERVER['PHP_SELF']), $command->getProcessedHelp()));
	}
	
	/**
	 * Tries to make a path relative to the project, which prints nicer.
	 *
	 * @param string $absolutePath
	 *
	 * @return string
	 */
	public function makePathRelative($absolutePath)
	{
		$projectRootDir = $this->container->getParameter('kernel.project_dir');
		
		return str_replace($projectRootDir, '.', realpath($absolutePath) ?: $absolutePath);
	}
}
