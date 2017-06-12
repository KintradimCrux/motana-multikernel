<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\HttpKernel;

use Motana\Bundle\MultiKernelBundle\HttpKernel\BootKernelRequest;

use Motana\Bundle\MultiKernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\HttpKernel\BootKernelRequest
 */
class BootKernelRequestTest extends TestCase
{
	/**
	 * @var BootKernelRequest
	 */
	protected static $request;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($env = null, $kernelName = 'boot')
	{
		// Default request parameters
		$serverVars = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		switch ($env) {
			// Request parameters as detected on the console
			case 'console':
				$serverVars = $_SERVER;
				break;
				
			// Request parameters containing an URI with appended query string
			case 'with_query_string':
				$serverVars = array_merge($serverVars, array(
					'QUERY_STRING' => 'panel=request',
					'REQUEST_URI' => '/web/foobar/controller/action?panel=request',
				));
				break;
				
			// Request parameters containing an URI not starting with a slash
			case 'without_slash':
				$serverVars = array_merge($serverVars, array(
					'REQUEST_URI' => 'web/foobar/controller/action',
				));
				break;
				
			// Request parameters for an app installed in a document root
			case 'webroot':
				$serverVars = array_merge($serverVars, array(
					'BASE' => '/',
					'PHP_SELF' => '/app.php',
					'REQUEST_URI' => '/foobar/controller/action',
					'SCRIPT_FILENAME' => '/home/user/public_html/app.php',
					'SCRIPT_NAME' => '/app.php',
				));
				break;
			
			// Request parameters containing an URI with no path info
			case 'no_pathinfo':
				$serverVars = array_merge($serverVars, array(
					'BASE' => '/web',
					'PHP_SELF' => '/web/app.php',
					'REQUEST_URI' => '/web/boot',
					'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
					'SCRIPT_NAME' => '/web/app.php',
				));
				break;
		}
		
		self::$request = new BootKernelRequest($_GET, $_REQUEST, array(), array(), array(), $serverVars, null, $kernelName);
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		// Check that the kernelName property has been initialized correctly
		$this->assertAttributeEquals('boot', 'kernelName', self::$request);
	}
	
	/**
	 * @covers ::getBaseUrl()
	 */
	public function testGetBaseUrl()
	{
		// Check that getBaseUrl() returns the correct path
		$this->assertEquals('/web/boot', self::$request->getBaseUrl());
		
		// Check that getBaseUrl() sets the baseUrl property
		$this->assertAttributeEquals('/web/boot', 'baseUrl', self::$request);
	}
	
	/**
	 * @covers ::preparePathInfo()
	 */
	public function testPreparePathInfo()
	{
		// Check that preparePathInfo() returns the correct path
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check that preparePathInfo() removes the query string from the uri
		$this->setUp('with_query_string');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check that preparePathInfo() prefixes the uri with a slash if required
		$this->setUp('without_slash');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check that preparePathInfo() returns a slash when there is no REQUEST_URI
		$this->setUp('console');
		$this->assertEquals('/', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check that preparePathInfo() strips the kernel name
		$this->setUp(null, 'foobar');
		$this->assertEquals('/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check that preparePathInfo() returns the REQUEST_URI when the base URL is '/'
		$this->setUp('webroot');
		$this->assertEquals('/foobar/controller/action', $this->callMethod(self::$request, 'preparePathInfo'));
		
		// Check that preparePathInfo() returns a slash if the REQUEST_URI equals the base URL
		$this->setUp('no_pathinfo');
		$this->assertEquals('/', $this->callMethod(self::$request, 'preparePathInfo'));
	}
}
