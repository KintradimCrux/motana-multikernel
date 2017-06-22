<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Asset;

use Symfony\Component\Asset\PathPackage as BasePackage;

/**
 * Replacement for the 'assets.path_package' service to remove '../' from asset URLs.
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
		return preg_replace('#/(?!\.\.)[^/]+/\.\./#', '/', parent::getBasePath());
	}
}
