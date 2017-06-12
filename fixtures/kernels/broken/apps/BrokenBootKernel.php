<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class BrokenBootKernel extends Motana\Bundle\MultiKernelBundle\HttpKernel\BootKernel
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getRootDir()
	 */
	public function getRootDir()
	{
		return $this->rootDir = __DIR__;
	}
}
