<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;

class AppKernel extends Kernel
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
		$bundles = array();
		
		$bundles[] = new FrameworkBundle();
		$bundles[] = new MotanaMultikernelBundle();
		
		if (class_exists('Symfony\\Bundle\\WebServerBundle\\WebServerBundle')) {
			$bundles[] = new \Symfony\Bundle\WebServerBundle\WebServerBundle();
		}
		
		return $bundles;
	}
}
