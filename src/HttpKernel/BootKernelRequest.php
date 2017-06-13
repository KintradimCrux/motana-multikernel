<?php

namespace Motana\Bundle\MultiKernelBundle\HttpKernel;

use Symfony\Component\HttpFoundation\Request;

/**
 * A request that appends the kernel name to the base path.
 *  
 * @author torr
 */
class BootKernelRequest extends Request
{
	// {{{ Properties
	
	/**
	 * @var Kernel
	 */
	protected $kernelName;
	
	// }}}
	// {{{ Constructor
	
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
	public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null, $kernelName = null)
	{
		parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
		
		$this->kernelName = $kernelName;
	}
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::getBaseUrl()
	 */
	public function getBaseUrl()
	{
		if (null === $this->baseUrl) {
			$baseUrl = parent::getBaseUrl();
			if ( ! empty($baseUrl)) {
				$this->baseUrl .= '/' . $this->kernelName;
			}
		}
		
		return $this->baseUrl;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::preparePathInfo()
	 */
	protected function preparePathInfo()
	{
		$baseUrl = $this->getBaseUrl();
		
		if ('' === ($requestUri = $this->getRequestUri())) {
			return '/';
		}
		
		// Remove the query string from REQUEST_URI
		if (false !== $pos = strpos($requestUri, '?')) {
			$requestUri = substr($requestUri, 0, $pos);
		}
		
		if ('' == $baseUrl) {
			$pathInfo = $requestUri;
		} elseif (false !== strpos($requestUri, $baseUrl)) {
			$pathInfo = substr($requestUri, strlen($baseUrl));
		} else {
			$baseUrl = dirname($baseUrl);
			$pathInfo = substr($requestUri, strpos($requestUri, '/', strlen($baseUrl)));
		}
		
		if ('' !== $baseUrl && (false === $pathInfo || '' === $pathInfo)) {
			// If substr() returns false then PATH_INFO is set to an empty string
			return '/';
		}
		
		return (string) $pathInfo;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\HttpFoundation\Request::prepareRequestUri()
	 */
	protected function prepareRequestUri()
	{
		$requestUri = parent::prepareRequestUri();
		
		if ($requestUri !== '' && $requestUri[0] !== '/') {
			$requestUri = '/' . $requestUri;
		}
		
		$this->server->set('REQUEST_URI', $requestUri);
		
		return $requestUri;
	}
	
	// }}}
}
