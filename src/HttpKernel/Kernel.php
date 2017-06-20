<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\HttpKernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Abstract base class for kernels.
 * 
 * @author torr
 */
abstract class Kernel extends BaseKernel
{
	// {{{ Properties
	
	/**
	 * @var string
	 */
	protected $cacheDir;
	
	/**
	 * @var string
	 */
	protected $logDir;
	
	// }}}
	// {{{ Constructor
	
	/**
	 * Constructor.
	 *
	 * @param string $environment The environment
	 * @param bool   $debug       Whether to enable debugging or not
	 */
	public function __construct($environment, $debug)
	{
		parent::__construct($environment, $debug);
		
		if ((PHP_VERSION_ID < 70000 && 'cli' !== PHP_SAPI && ! in_array($environment, ['test', 'dev'])) || isset($GLOBALS['KernelTest_constructor'])) {
			$this->loadClassCache();
		}
	}
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getCacheDir()
	 */
	public function getCacheDir()
	{
		if (null === $this->cacheDir) {
			$this->cacheDir = dirname(dirname($this->getRootDir())) . '/var/cache/' . $this->getEnvironment() . '/' . $this->getName();
		}
		
		return $this->cacheDir;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getLogDir()
	 */
	public function getLogDir()
	{
		if (null === $this->logDir) {
			$this->logDir = dirname(dirname($this->getRootDir())) . '/var/logs/' . $this->getName();
		}
		
		return $this->logDir;
	}
	
	// }}}
	// {{{ Interface KernelInterface
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\KernelInterface::registerContainerConfiguration()
	 */
	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		$loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
	}
	
	// }}}
}
