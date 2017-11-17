<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\HttpKernel;

use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebServerBundle\WebServerBundle;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Glob;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Abstract kernel base class for multi-kernel applications.
 *
 * The boot kernel routes requests to the kernel matching the request URI,
 * using a Request class which adds the kernel name to the base URI.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class BootKernel extends Kernel
{
	/**
	 * Kernel metadata.
	 *
	 * @var array
	 */
	private $kernels = [];
	
	/**
	 * Kernel instances.
	 *
	 * @var array
	 */
	private $instances = [];
	
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
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getName()
	 */
	final public function getName()
	{
		if (null === $this->name) {
			$ref = new \ReflectionClass($this);
			$this->name = Container::underscore(substr($ref->getShortName(), 0, -6));
		}
		
		return $this->name;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::getCacheDir()
	 */
	public function getCacheDir()
	{
		// Initialize cache dir if required
		if (null === $this->cacheDir) {
			$this->cacheDir = dirname($this->getRootDir()) . '/var/cache/' . $this->getName() . '/' . $this->getEnvironment();
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
			$this->logDir = dirname($this->getRootDir()) . '/var/logs/' . $this->getName();
		}
		
		// Return the log dir
		return $this->logDir;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::boot()
	 */
	public function boot()
	{
		// No action required if already booted
		if (true === $this->booted) {
			return;
		}
		
		// Boot the kernel
		parent::boot();
		
		// Load kernel data
		$this->loadKernelData();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::handle()
	 */
	final public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		// Override the factory of the Request class
		Request::setFactory([ $this, 'requestFactory' ]);
		
		// Kernel name detected in request
		if ($kernel = $this->loadKernel($this->getKernelFromRequest($request))) {
		}
		
		// Load default kernel
		elseif ($kernel = $this->loadKernel($this->getContainer()->getParameter('motana.multikernel.default'))) {
		}
		
		// No kernel available
		else {
			throw new NotFoundHttpException('Unable to load the default kernel. Did you forget to set the motana_multikernel.default in config.yml?');
		}
		
		// Generate a BootKernelRequest from the Request
		if ( ! $request instanceof BootKernelRequest) {
			$request = BootKernelRequest::createFromRequest($request, $kernel->getName());
		}

		// Set the kernel start time to make the time consumption of the boot kernel visible in profiler
		if ($this->debug) {
			$kernel->startTime = $this->startTime;
		}
		
		// Let the kernel handle the request
		try {
			$response = $kernel->handle($request, $type, $catch);
		}
		
		// Re-throw any thrown exception
		catch (\Exception $e) {
			throw $e;
		}
		
		// Reset the factory of the Request class
		finally {
			Request::setFactory(null);
		}
		
		// Return the response
		return $response;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\Kernel::terminate()
	 */
	final public function terminate(Request $request, Response $response)
	{
		// Nothing to do if the kernel was never booted or there are no other kernel instances
		if (false === $this->booted || empty($this->instances)) {
			return;
		}
		
		// Get the request
		$request = $this->request ?: $request;
		
		// Terminate all kernels
		foreach ($this->getKernels() as $kernel) {
			if ( ! $kernel instanceof self) {
				$kernel->terminate($request, $response);
			} else {
				parent::terminate($request, $response);
			}
		}
		
		// Clear the instances array
		$this->instances = [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpKernel\KernelInterface::registerBundles()
	 */
	public function registerBundles()
	{
		$bundles = [];
		
		$bundles[] = new FrameworkBundle();
		$bundles[] = new MotanaMultikernelBundle();
		
		if ('dev' === $this->getEnvironment()) {
			$bundles[] = new WebServerBundle();
		}
		
		$bundles[] = new TwigBundle();
		
		return $bundles;
	}

	/**
	 * Enable or disable the use of the AppCache class.
	 *
	 * @param string $appCache Boolean indicating whether to use the AppCache or not
	 */
	final public function useAppCache($appCache = true)
	{
		$this->useAppCache = (boolean) $appCache;
	}
	
	/**
	 * Removes invalid characters from a kernel name and adds an underscore prefix
	 * to kernel names beginning with a number.
	 *
	 * @param string $kernelName Kernel name
	 * @return string
	 */
	final public static function sanitizeKernelName($kernelName)
	{
		// Strip invalid characters
		$kernelName = preg_replace('/[^a-zA-Z0-9_]+/', '', $kernelName);
		
		// Add an underscore prefix to kernel names beginning with a number
		if (ctype_digit($kernelName[0])) {
			$kernelName = '_' . $kernelName;
		}
		
		// Return the sanitized kernel name
		return $kernelName;
	}
	
	/**
	 * Camelize a kernel name.
	 *
	 * @param string $kernelName Kernel name
	 * @return string
	 */
	final public static function camelizeKernelName($kernelName)
	{
		// Add an underscore prefix to kernel names beginning with a number
		$prefix = (ctype_digit($kernelName[0]) || '_' == $kernelName[0]) ? '_' : '';
		
		// Return the camelized kernel name
		return $prefix . Container::camelize(self::sanitizeKernelName($kernelName));
	}
	
	/**
	 * Load kernel data from cache.
	 *
	 * @param string $name Cache file name
	 * @param string $extension Cache file extension
	 */
	private function loadKernelData($name = 'kernels', $extension = 'php')
	{
		// Determine the path to the cache file
		$file = $this->getCacheDir() . '/' . $name . '.' . $extension;
		
		// Get the resource checker for the cache file
		$projectDirectory = $this->getProjectDir();
		$resource = new DirectoryResource($projectDirectory . '/apps', Glob::toRegex('*.php'));
		$checker = new SelfCheckingResourceChecker($resource);
		$cache = new ResourceCheckerConfigCache($file, [ $checker ]);
		
		// Cache is fresh
		if ($cache->isFresh())
		{
			// Load data from the file
			$this->kernels = include($file);
			
			// Return to caller
			return;
		}
		
		// Process all app directories
		$this->kernels = [];
		foreach (new \DirectoryIterator($projectDirectory. '/apps') as $dir)
		{
			// Skip non-directories and dot files
			if ( ! $dir->isDir() || $dir->isDot()) {
				continue;
			}
			
			// Get the kernel name and the Kernel and Cache class names
			$kernelDir = $dir->getBasename();
			$kernelName = self::sanitizeKernelName($kernelDir);
			$kernelCamel = self::camelizeKernelName($kernelName);
			$kernelClass = $kernelCamel . 'Kernel';
			$cacheClass = $kernelCamel . 'Cache';
			
			// Skip directories not containing a kernel
			if ( ! is_file($dir->getPathname() . '/' . $kernelClass . '.php')) {
				continue;
			}
			
			// Check if there is a Cache class
			$cacheClassExists = is_file($dir->getPathname() . '/' . $cacheClass . '.php');
			
			// Add the kernel
			$this->kernels[$kernelName] = [
				'kernel' => $kernelDir . '/' . $kernelClass . '.php',
				'cache' => $cacheClassExists ? $kernelDir . '/' . $cacheClass . '.php' : false,
			];
		}
		
		// Sort the kernel list
		ksort($this->kernels);
		
		// Update the cache file
		$cache->write(sprintf(implode(PHP_EOL, [
			'<?php',
			'',
			'// This file has been auto-generated by the Symfony Cache component',
			'',
			'return %s;',
			''
		]), var_export($this->kernels, true)), [ $resource ]);
		
		// Invalidate the cache file
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($cache->getPath(), true);
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
		// Return data for the kernel if available
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
		// Boot the kernel if required
		if (false === $this->booted) {
			$this->boot();
		}
		
		// Get PATH_INFO from the request
		$pathInfo = $request->getPathInfo();
		
		// Process all available kernels
		foreach ($this->kernels as $kernelName => $info)
		{
			// Return the kernel name if found in the URL with leading and trailing slash
			if (0 === strpos($pathInfo, '/' . $kernelName . '/')) {
				return $kernelName;
			}
			
			// Return the kernel name if the URL equals a leading slash followed by the kernel name
			if ($pathInfo === '/' . $kernelName) {
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
		// Boot the kernel if required
		if (false === $this->booted) {
			$this->boot();
		}
		
		// Return an already existing instance if available
		if (isset($this->instances[$kernelName])) {
			return $this->instances[$kernelName];
		}
		
		// Return the boot kernel if the name matches it
		if ($kernelName === $this->getName()) {
			return $this->instances[$kernelName] = $this;
		}
		
		// Load kernel data
		if ($data = $this->getKernelData($kernelName))
		{
			// Load the kernel class
			require_once($this->rootDir . DIRECTORY_SEPARATOR . $data['kernel']);
			
			// Check the kernel class actually exists
			if ( ! class_exists($class = basename($data['kernel'], '.php'))) {
				throw new \RuntimeException(sprintf('Kernel class "%s" does not exist. Did you name your kernel class correctly?', $class));
			}
			
			// Create a new kernel instance
			$kernel = new $class($this->environment, $this->debug);
			
			// Not using the app cache or no cache class exists, return the kernel instance
			if (false === $data['cache'] || ! $this->useAppCache) {
				return $this->instances[$kernelName] = $kernel;
			}
			
			// Load the cache class
			require_once($this->rootDir . DIRECTORY_SEPARATOR . $data['cache']);
			
			// Check the cache class actually exists
			if ( ! class_exists($class = basename($data['cache'], '.php'))) {
				throw new \RuntimeException(sprintf('Cache class "%s" does not exist. Did you name your cache class correctly?', $class));
			}
			
			// Create a new cache instance for the kernel
			$kernel = new $class($kernel);
			
			// Return the cache instance
			return $this->instances[$kernelName] = $kernel;
		}
	}
	
	/**
	 * Returns a kernel referenced by its name.
	 *
	 * @param string $kernelName An kernel name
	 * @return \Motana\Component\HttpKernel\Kernel
	 */
	final public function getKernel($kernelName)
	{
		return $this->loadKernel($kernelName);
	}
	
	/**
	 * Returns the Kernel instances loaded for the active application.
	 *
	 * @return \Symfony\Component\HttpKernel\Kernel[]
	 */
	final public function getKernels()
	{
		// Boot the kernel if required
		if (false === $this->booted) {
			$this->boot();
		}
		
		// Load the boot kernel
		$this->loadKernel('boot');
		
		// Load each application kernel
		foreach (array_keys($this->kernels) as $kernelName) {
			$this->loadKernel($kernelName);
		}
		
		// Return kernel instances
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
	final public function requestFactory(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
	{
		// Check a kernel is already loaded
		if (empty($this->instances)) {
			throw new \RuntimeException('A kernel must be loaded before BootKernel::requestFactory can be used.');
		}
		
		// Get the kernel of the application
		/** @var Kernel $kernel */
		$kernel = reset($this->instances);
		
		if ($kernel instanceof HttpCache) {
			$kernel = $kernel->getKernel();
		}
		
		// Return a new request
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
}
