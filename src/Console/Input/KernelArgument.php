<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Console\Input;

/**
 * A conditional argument for the kernel name.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class KernelArgument extends ConditionalArgument
{
	// {{{ Properties
	
	/**
	 * Kernels to recognize.
	 * 
	 * @var array
	 */
	private $kernels = array();
	
	// }}}
	// {{{ Constructor
	
	/**
	 * Constructor.
	 *
	 * @param string $name Argument name
	 * @param integer $mode Argument mode
	 * @param string $description Argument description
	 * @param array $kernels Kernels to recognize as argument
	 * @throws InvalidArgumentException
	 */
	public function __construct($name, $mode = null, $description = '', array $kernels = array())
	{
		parent::__construct($name, $mode, $description);
		
		$this->kernels = $kernels;
	}
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Component\Console\Input\ConditionalArgument::condition()
	 */
	protected function condition($value)
	{
		return in_array($value, $this->kernels);
	}
	
	// }}}
}
