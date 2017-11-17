<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\HttpKernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Abstract base class for kernels.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class Kernel extends BaseKernel
{
	/**
	 * @var string
	 */
	protected $cacheDir;
	
	/**
	 * @var string
	 */
	protected $logDir;
	
	/**
	 * Constructor.
	 *
	 * @param string $environment The environment
	 * @param bool   $debug       Whether to enable debugging or not
	 */
	public function __construct($environment, $debug)
	{
		// Call parent constructor
		parent::__construct($environment, $debug);
		
		// Load class cache for php versions below 7.0
		if (PHP_VERSION_ID < 70000 && ! in_array($environment, [ 'test', 'dev' ])) {
			$this->loadClassCache();
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getCacheDir()
	 */
	public function getCacheDir()
	{
		// Initialize cache dir if required
		if (null === $this->cacheDir) {
			$this->cacheDir = dirname(dirname($this->getRootDir())) . '/var/cache/' . $this->getName() . '/' . $this->getEnvironment();
		}
		
		// Return the cache dir
		return $this->cacheDir;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getLogDir()
	 */
	public function getLogDir()
	{
		// Initialize log dir if required
		if (null === $this->logDir) {
			$this->logDir = dirname(dirname($this->getRootDir())) . '/var/logs/' . $this->getName();
		}
		
		// Return the log dir
		return $this->logDir;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\KernelInterface::registerContainerConfiguration()
	 */
	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		// Load the configuration for the kernel environment
		$loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
	}
	
	/**
	 * Gets the container class.
	 *
	 * @return string The container class
	 */
	protected function getContainerClass()
	{
		return lcfirst(Container::camelize($this->name)).ucfirst($this->environment).($this->debug ? 'Debug' : '').'ProjectContainer';
	}
}
