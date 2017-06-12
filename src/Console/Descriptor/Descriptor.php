<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Console\Descriptor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\Descriptor as BaseDescriptor;

/**
 * Extension for the Symfony Descriptor base class.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class Descriptor extends BaseDescriptor
{
	// {{{ Helper methods
	
	/**
	 * Returns the processed help for a command.
	 *
	 * @param Command $command Command to inspect
	 * @return string
	 */
	protected function getProcessedHelp(Command $command)
	{
		return rtrim(str_replace('php '.$_SERVER['PHP_SELF'], $_SERVER['PHP_SELF'], $command->getProcessedHelp()));
	}
	
	// }}}
}
