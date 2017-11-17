<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Asset;

use Symfony\Component\Asset\PathPackage as BasePackage;

/**
 * Replacement for the 'assets.path_package' service to return canonical paths for assets.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class PathPackage extends BasePackage
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Asset\PathPackage::getBasePath()
	 */
	public function getBasePath()
	{
		// Return a canonical path for the resource
		return preg_replace('#/(?!\.\.)[^/]+/\.\./#', '/', parent::getBasePath());
	}
}
