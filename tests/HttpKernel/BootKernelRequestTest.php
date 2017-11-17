<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\HttpKernel;

use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest
 * @testdox Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest
 */
class BootKernelRequestTest extends TestCase
{
	/**
	 * The request object to test.
	 *
	 * @var BootKernelRequest
	 */
	protected static $request;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($env = null, $kernelName = 'boot')
	{
		// Default request parameters
		$serverVars = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		switch ($env) {
			// Request parameters as detected on the console
			case 'console':
				$serverVars = $_SERVER;
				break;
				
			// Request parameters containing an URI with appended query string
			case 'with_query_string':
				$serverVars = array_merge($serverVars, [
					'QUERY_STRING' => 'panel=request',
					'REQUEST_URI' => '/web/foobar/controller/action?panel=request',
				]);
				break;
				
			// Request parameters containing an URI not starting with a slash
			case 'without_slash':
				$serverVars = array_merge($serverVars, [
					'REQUEST_URI' => 'web/foobar/controller/action',
				]);
				break;
				
			// Request parameters for an app installed in a document root
			case 'webroot':
				$serverVars = array_merge($serverVars, [
					'BASE' => '/',
					'PHP_SELF' => '/app.php',
					'REQUEST_URI' => '/foobar/controller/action',
					'SCRIPT_FILENAME' => '/var/www/app.php',
					'SCRIPT_NAME' => '/app.php',
				]);
				break;
			
			// Request parameters for an app installed in a public_html directory
			case 'homedir':
				$serverVars = array_merge($serverVars, [
					'BASE' => '/~user/web',
					'PHP_SELF' => '/~user/web/app.php',
					'REQUEST_URI' => '/~user/web/foobar/controller/action',
					'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
					'SCRIPT_NAME' => '/~user/web/app.php',
				]);
				break;
				
			// Request parameters containing an URI with no path info
			case 'no_pathinfo':
				$serverVars = array_merge($serverVars, [
					'BASE' => '/web',
					'PHP_SELF' => '/web/app.php',
					'REQUEST_URI' => '/web/',
					'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
					'SCRIPT_NAME' => '/web/app.php',
				]);
				break;
		}
		
		// Create a new request with parameters from above
		self::$request = new BootKernelRequest($_GET, $_REQUEST, [], [], [], $serverVars, null, $kernelName);
	}
	
	/**
	 * @covers ::createFromRequest()
	 * @testdox createFromRequest() returns a BootKernelRequest
	 */
	public function test_createFromRequest()
	{
		// Create a request
		$request = new Request($_GET, $_REQUEST, [], [], [], array_merge($_SERVER, [
			'BASE' => '/',
			'PHP_SELF' => '/app.php',
			'REQUEST_URI' => '/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/app.php',
			'SCRIPT_NAME' => '/app.php',
		]));
		
		// Create a BootKernelRequest from the Request
		$request = BootKernelRequest::createFromRequest($request, 'app');
		
		// Check the correct base URL is returned
		$this->assertEquals('/app', $request->getBaseUrl());
		
		// Check the correct pathInfo is returned
		$this->assertEquals('/controller/action', $request->getPathInfo());
	}
	
	/**
	 * @covers ::__construct()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		// Check the kernelName property has been initialized correctly
		$this->assertAttributeEquals('boot', 'kernelName', self::$request);
	}
	
	/**
	 * @covers ::getBaseUrl()
	 * @testdox getBaseUrl() returns the correct URL prefix
	 */
	public function test_getBaseUrl()
	{
		// Check the correct path is returned
		$this->assertEquals('/web', self::$request->getBaseUrl());
		
		// Check the baseUrl property has been set
		$this->assertAttributeEquals('/web', 'baseUrl', self::$request);
		
		// Set up the 'foobar' kernel
		$this->setUp(null, 'foobar');
		
		// Check the correct path is returned
		$this->assertEquals('/web/foobar', self::$request->getBaseUrl());
		
		// Check the baseUrl property has been set
		$this->assertAttributeEquals('/web/foobar', 'baseUrl', self::$request);
	}
	
	/**
	 * @covers ::preparePathInfo()
	 * @testdox preparePathInfo() returns the correct path
	 */
	public function test_preparePathInfo()
	{
		// Check the correct uri is returned
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check the query string is removed from the uri
		$this->setUp('with_query_string');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check a slash prefix is added when missing
		$this->setUp('without_slash');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check a slash is returned when there is no REQUEST_URI
		$this->setUp('console');
		$this->assertEquals('/', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check the kernel name is stripped
		$this->setUp(null, 'foobar');
		$this->assertEquals('/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check the REQUEST_URI is returned when the base URL is '/'
		$this->setUp('webroot');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));

		// Check the remaining part of REQUEST_URI is returned when the base URL is not '/'
		$this->setUp('homedir', 'app');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check a slash is returned when the REQUEST_URI equals the base URL
		$this->setUp('no_pathinfo');
		$this->assertEquals('/', $this->callMethod(self::$request, 'preparePathInfo'));
	}
	
	/**
	 * @covers ::prepareRequestUri()
	 * @testdox prepareRequestUri() returns the correct URI
	 */
	public function test_prepareRequestUri()
	{
		// Check the correct uri is returned
		$this->assertEquals('/web/foobar/controller/action', $this->callMethod(self::$request, 'prepareRequestUri'));
		
		// Check the correct uri is returned without leading slash
		$this->setUp('without_slash');
		$this->assertEquals('/web/foobar/controller/action', $this->callMethod(self::$request, 'prepareRequestUri'));
	}
	
	/**
	 * @covers ::prepareBaseUrl()
	 * @testdox prepareBaseUrl() initializes server variables for Symfony WebTestCase
	 */
	public function test_prepareBaseUrl()
	{
		// Clear the SCRIPT_NAME server variable
		self::$request->server->set('SCRIPT_NAME', '');
		
		// Get the base URL
		$baseUrl = self::$request->getBaseUrl();
		
		// Check that SCRIPT_NAME and SCRIPT_FILENAME have been set correctly
		$this->assertEquals('/app.php', self::$request->server->get('SCRIPT_NAME'));
		$this->assertEquals('/var/www/app.php', self::$request->server->get('SCRIPT_FILENAME'));
	}
}
