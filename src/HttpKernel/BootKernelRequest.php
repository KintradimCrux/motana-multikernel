<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\HttpKernel;

use Symfony\Component\HttpFoundation\Request;

/**
 * A request that appends the kernel name to the base path.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class BootKernelRequest extends Request
{
	/**
	 * @var Kernel
	 */
	protected $kernelName;
	
	/**
	 * Create a BootKernelRequest from an existing Request instance.
	 *
	 * @param Request $request Request to read parameters from
	 * @param string $kernelName The kernel name
	 * @return \Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest
	 */
	public static function createFromRequest(Request $request, $kernelName)
	{
		return new self(
			$request->query->all(),
			$request->request->all(),
			$request->attributes->all(),
			$request->cookies->all(),
			$request->files->all(),
			$request->server->all(),
			$request->getContent(),
			$kernelName
		);
	}
	
	/**
	 * Constructor.
	 *
	 * @param array           $query      The GET parameters
	 * @param array           $request    The POST parameters
	 * @param array           $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
	 * @param array           $cookies    The COOKIE parameters
	 * @param array           $files      The FILES parameters
	 * @param array           $server     The SERVER parameters
	 * @param string|resource $content    The raw body data
	 * @param string          $kernelName The kernel name
	 */
	public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null, $kernelName = null)
	{
		parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
		
		$this->kernelName = $kernelName;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::getBaseUrl()
	 */
	public function getBaseUrl()
	{
		// Initialize base url if required
		if (null === $this->baseUrl)
		{
			// Call parent method
			$baseUrl = parent::getBaseUrl();
			
			// Append kernel name to non-empty base url
			if ( ! empty($baseUrl) && 'boot' !== $this->kernelName) {
				$this->baseUrl = rtrim($baseUrl, '/'.DIRECTORY_SEPARATOR) . '/' . $this->kernelName;
			}
		}
		
		// Return the base url
		return $this->baseUrl;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::preparePathInfo()
	 */
	protected function preparePathInfo()
	{
		// Get the base url
		$baseUrl = $this->getBaseUrl();
		
		// Return a slash if REQUEST_URI is empty
		if ('' === ($requestUri = $this->getRequestUri())) {
			return '/';
		}
		
		// Remove the query string from REQUEST_URI
		if (false !== $pos = strpos($requestUri, '?')) {
			$requestUri = substr($requestUri, 0, $pos);
		}
		
		// Generate path info
		if ('' == $baseUrl || '/' == $baseUrl) {
			$pathInfo = $requestUri;
		} elseif (false !== strpos($requestUri, $baseUrl)) {
			$pathInfo = substr($requestUri, strlen($baseUrl));
		} else {
			$baseUrl = dirname($baseUrl);
			$pathInfo = substr($requestUri, strpos($requestUri, '/', strlen($baseUrl)));
		}
		
		// Return PATH_INFO
		return (string) $pathInfo;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::prepareRequestUri()
	 */
	protected function prepareRequestUri()
	{
		// Call parent method
		$requestUri = parent::prepareRequestUri();
		
		// Prefix REQUEST_URI with a slash if required
		if ($requestUri !== '' && $requestUri[0] !== '/') {
			$requestUri = '/' . $requestUri;
		}
		
		// Set REQUEST_URI
		$this->server->set('REQUEST_URI', $requestUri);
		
		// Return REQUEST_URI
		return $requestUri;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::prepareBaseUrl()
	 */
	protected function prepareBaseUrl()
	{
		// The client used in Symfony\Bundle\FrameworkBundle\Test\WebTestCase initializes
		// SCRIPT_NAME and SCRIPT_FILENAME with an empty string. Fill them with dummy
		// values in this case.
		if ('' === $this->server->get('SCRIPT_NAME')) {
			$this->server->set('SCRIPT_NAME', '/app.php');
			$this->server->set('SCRIPT_FILENAME', '/var/www/app.php');
		}
		
		// Let the parent method prepare the base URL
		$baseUrl = parent::prepareBaseUrl();
		
		// Return the base url, or a slash when the base URL is empty
		return $baseUrl ?: '/';
	}
}
