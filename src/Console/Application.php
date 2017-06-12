<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\HttpKernel\KernelInterface;

use Motana\Bundle\MultiKernelBundle\Command\HelpCommand;
use Motana\Bundle\MultiKernelBundle\Command\ListCommand;

/**
 * Base application for the Multi-Kernel extension of the Motana framework.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class Application extends BaseApplication
{
	// {{{ Constructor
	
	/**
	 * Constructor.
	 *
	 * @param KernelInterface $kernel A KernelInterface instance
	 * @param boolean $autoExit Boolean indicating to enable the auto-exit feature (default: false)
	 */
	public function __construct(KernelInterface $kernel, $autoExit = false)
	{
		parent::__construct($kernel);
		
		$this->setAutoExit($autoExit);
	}
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::getName()
	 */
	public function getName()
	{
		return 'Motana Multi-Kernel App Console - Symfony';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Application::getDefaultCommands()
	 */
	protected function getDefaultCommands()
	{
		return array(
			new HelpCommand(),
			new ListCommand()
		);
	}
	
	// }}}
}
