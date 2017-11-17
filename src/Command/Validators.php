<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\Validators as SensioValidators;

/**
 * Abstract class containing validator methods for commands.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class Validators extends SensioValidators
{
	/**
	 * Validates a new kernel name.
	 *
	 * @param string $kernelName Kernel name to validate
	 * @param string $projectDir Project directory
	 * @throws \InvalidArgumentException
	 */
	public static function validateNewKernelName($kernelName, $projectDir)
	{
		// Check the kernel name contains only valid characters
		if ( ! preg_match('/^[a-zA-Z0-9_]+$/', $kernelName)) {
			throw new \InvalidArgumentException('The kernel name contains invalid characters.');
		}
		
		// Check the kernel name is allowed
		if (in_array($kernelName, [ 'boot', 'config' ])) {
			throw new \InvalidArgumentException(sprintf('The kernel name is not allowed.'));
		}
		
		// Check the kernel name is not already in use
		if (is_dir($projectDir . '/apps/' . $kernelName)) {
			throw new \InvalidArgumentException('The kernel name is already in use.');
		}
		
		// Return the kernel name
		return $kernelName;
	}

	/**
	 * Validate a path is relative to the project directory.
	 *
	 * @param string $path Path to check
	 * @throws \InvalidArgumentException
	 */
	public static function validateRelativePath($path)
	{
		// Check the path is not absolute
		if ('/' === $path[0]) {
			throw new \InvalidArgumentException('The path must be relative to the project directory');
		}
		
		// Check the path does not contain '../'
		if (false !== strpos($path, '../')) {
			throw new \InvalidArgumentException('The path must be relative to the project directory');
		}
		
		// Return the path
		return $path;
	}
}
