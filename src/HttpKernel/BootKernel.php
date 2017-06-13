<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\HttpKernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler\AddKernelsToCachePass;
use Motana\Bundle\MultiKernelBundle\MotanaMultiKernelBundle;

/**
 * Abstract boot kernel base class for multi-kernel applications, which boots
 * one or more kernels. The first booted kernel is used to handle web requests.
 * 
 * @author torr
 */
abstract class BootKernel extends Kernel
{
	// {{{ Properties
	
	/**
	 * Kernel metadata.
	 * 
	 * @var array
	 */
	private $kernels = array();
	
	/**
	 * Kernel instances.
	 * 
	 * @var array
	 */
	private $instances = array();
	
	/**
	 * Boolean indicating whether to use the AppCache class or not.
	 *
	 * @var boolean
	 */
	private $useAppCache;
	
	/**
	 * The delegated request.
	 * 
	 * @var BootKernelRequest
	 */
	private $request;
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getName()
	 */
	final public function getName()
	{
		return 'boot';
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getCacheDir()
	 */
	public function getCacheDir()
	{
		if (null === $this->cacheDir) {
			$this->cacheDir = dirname($this->getRootDir()) . '/var/cache/' . $this->getEnvironment() . '/' . $this->getName();
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
			$this->logDir = dirname($this->getRootDir()) . '/var/logs/' . $this->getName();
		}
		
		return $this->logDir;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::boot()
	 */
	public function boot()
	{
		if (true === $this->booted) {
			return;
		}
		
		parent::boot();
		
		$this->loadKernelData();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::handle()
	 */
	final public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		Request::setFactory(array($this, 'requestFactory'));
		
		if ($kernel = $this->loadKernel($this->getKernelFromRequest($request))) {
			return $kernel->handle(Request::createFromGlobals(), $type, $catch);
		} elseif ($kernel = $this->loadKernel($this->getContainer()->getParameter('motana_multi_kernel.default'))) {
			return $kernel->handle(Request::createFromGlobals(), $type, $catch);
		}
		
		throw new NotFoundHttpException(sprintf('Unable to find the kernel for path "%s". The application is wrongly configured.', $request->getPathInfo()));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::terminate()
	 */
	final public function terminate(Request $request, Response $response)
	{
		if (false === $this->booted || empty($this->instances)) {
			return;
		}
		
		$request = $this->request ?: $request;
		
		foreach ($this->instances as $kernel) {
			if ( ! $kernel instanceof self) {
				$kernel->terminate($request, $response);
			} else {
				parent::terminate($request, $response);
			}
		}
		
		$this->instances = array();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\KernelInterface::registerBundles()
	 */
	public function registerBundles()
	{
		$bundles = array();
		
		$bundles[] = new FrameworkBundle();
		$bundles[] = new MotanaMultiKernelBundle();
		
		if (class_exists('Symfony\\Bundle\\WebServerBundle\\WebServerBundle')) {
			$bundles[] = new \Symfony\Bundle\WebServerBundle\WebServerBundle();
		}
		
		return $bundles;
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::buildContainer()
	 */
	protected function buildContainer()
	{
		$container = parent::buildContainer();
		
		$container->addCompilerPass(new AddKernelsToCachePass($this));
		
		return $container;
	}
	
	// }}}
	// {{{ BootKernel public interface
	
	/**
	 * Enable or disable the use of the AppCache class.
	 *
	 * @param string $appCache Boolean indicating whether to use the AppCache or not
	 */
	final public function useAppCache($appCache = true)
	{
		$this->useAppCache = (boolean) $appCache;
	}
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Store kernel data in cache.
	 *
	 * @param array $applications
	 */
	final public function setKernelData(array $applications)
	{
		file_put_contents($this->getCacheDir() . '/kernels.php', sprintf('<?php return %s;', var_export($applications, true)));
	}
	
	/**
	 * Load kernel data from cache.
	 *
	 * @param string $name Cache file name
	 * @param string $extension Cache file extension
	 */
	private function loadKernelData($name = 'kernels', $extension = 'php')
	{
		if (is_file($file = $this->getCacheDir() . '/' . $name . '.' . $extension)) {
			$this->kernels = include($file);
		}
	}
	
	/**
	 * Returns the data for a kernel.
	 *
	 * @param string $kernelName A kernel name
	 * @return array
	 */
	private function getKernelData($kernelName)
	{
		if (isset($this->kernels[$kernelName])) {
			return $this->kernels[$kernelName];
		}
	}
	
	/**
	 * Returns the kernel name detected in the REQUEST_URI.
	 *
	 * @param Request $request Request object
	 * @return NULL|string
	 */
	private function getKernelFromRequest(Request $request)
	{
		if (false === $this->booted) {
			$this->boot();
		}
		
		$pathInfo = $request->getPathInfo();
		
		foreach ($this->kernels as $kernelName => $info) {
			if (0 === strpos($pathInfo, '/' . $kernelName . '/')) {
				return $kernelName;
			}
		}
	}
	
	/**
	 * Load and instantiate a kernel.
	 * 
	 * @param string $kernelName A kernel name
	 * @throws \RuntimeException
	 * @return \Motana\Component\HttpKernel\Kernel
	 */
	private function loadKernel($kernelName)
	{
		if (false === $this->booted) {
			$this->boot();
		}
		
		if (isset($this->instances[$kernelName])) {
			return $this->instances[$kernelName];
		}
		
		if ($kernelName=== $this->getName()) {
			return $this->instances[$kernelName] = $this;
		}
		
		if ($data = $this->getKernelData($kernelName)) {
			require_once($this->rootDir . DIRECTORY_SEPARATOR . $data['kernel']);
			if (false !== $data['cache']) {
				require_once($this->rootDir . DIRECTORY_SEPARATOR . $data['cache']);
			}
			
			if ( ! class_exists($class = basename($data['kernel'], '.php'))) {
				throw new \RuntimeException(sprintf('Kernel class "%s" does not exist. Did you name your kernel class correctly?', $class));
			}
			
			$kernel = new $class($this->environment, $this->debug);
			
			if ((false === $data['cache'] || 'cli' === PHP_SAPI || ! $this->useAppCache) && ! isset($GLOBALS['BootKernelTest_loadKernel'])) {
				return $this->instances[$kernelName] = $kernel;
			}

			if ( ! class_exists($class = basename($data['cache'], '.php'))) {
				throw new \RuntimeException(sprintf('Cache class "%s" does not exist. Did you name your cache class correctly?', $class));
			}
			
			$kernel = new $class($kernel);
			Request::enableHttpMethodParameterOverride();
			
			return $this->instances[$kernelName] = $kernel;
		}
	}
	
	/**
	 * Returns the kernel for an application name.
	 *
	 * @param string $applicationName An application name
	 * @return \Motana\Component\HttpKernel\Kernel
	 */
	final public function getKernel($applicationName)
	{
		return $this->loadKernel($applicationName);
	}
	
	/**
	 * Returns the Kernel instances loaded for the active application.
	 *
	 * @return \Symfony\Component\HttpKernel\Kernel[]
	 */
	final public function getKernels()
	{
		if (false === $this->booted) {
			$this->boot();
		}
		
		$this->loadKernel('boot');
		
		foreach (array_keys($this->kernels) as $applicationName) {
			$this->loadKernel($applicationName);
		}
		
		return $this->instances;
	}
	
	/**
	 * Request factory for the BootKernel.
	 *
	 * @param array $query The GET parameters
	 * @param array $request The POST parameters
	 * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
	 * @param array $cookies The COOKIE parameters
	 * @param array $files The FILES parameters
	 * @param array $server The SERVER parameters
	 * @param string|resource $content The raw body data
	 * @param string $kernelName The kernel name
	 * @return \Motana\Component\HttpKernel\BootKernelRequest
	 */
	final public function requestFactory(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
	{
		if (empty($this->instances)) {
			throw new \RuntimeException('A kernel must be loaded before BootKernel::requestFactory can be used.');
		}
		
		/** @var Kernel $kernel */
		$kernel = reset($this->instances);
		
		if ($kernel instanceof HttpCache) {
			$kernel = $kernel->getKernel();
		}
		
		return $this->request = new BootKernelRequest($query, $request, $attributes, $cookies, $files, $server, $content, $kernel->getName());
	}
	
	/**
	 * Returns the request delegated to the active application.
	 *
	 * @return \Motana\Component\HttpKernel\BootKernelRequest
	 */
	final public function getRequest()
	{
		return $this->request;
	}
	
	// }}}
}
