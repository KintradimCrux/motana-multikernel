<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Input;

/**
 * A conditional argument for the kernel name.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ConditionalKernelArgument extends ConditionalArgument
{
	/**
	 * Kernels to recognize.
	 *
	 * @var array
	 */
	private $kernels = [];
	
	/**
	 * Constructor.
	 *
	 * @param string $name Argument name
	 * @param integer $mode Argument mode
	 * @param string $description Argument description
	 * @param array $kernels Kernels to recognize as argument
	 * @throws InvalidArgumentException
	 */
	public function __construct($name, $mode = null, $description = '', array $kernels = [])
	{
		parent::__construct($name, $mode, $description);
		
		$this->kernels = $kernels;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Component\Console\Input\ConditionalArgument::condition()
	 */
	protected function condition($value)
	{
		return in_array($value, $this->kernels);
	}
}
