<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Motana\Bundle\MultiKernelBundle\HttpKernel\Kernel;

class BrokenCacheKernel extends Kernel
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getRootDir()
	 */
	public function getRootDir()
	{
		return $this->rootDir = __DIR__;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\KernelInterface::registerBundles()
	 */
	public function registerBundles()
	{
		return array(
		);
	}
}
