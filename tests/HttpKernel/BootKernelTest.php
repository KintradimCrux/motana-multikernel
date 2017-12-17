<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\HttpKernel;

use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
 * @testdox Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
 */
class BootKernelTest extends KernelTestCase
{
	/**
	 * Array containing the kernel names onKernelTerminate() was called for.
	 *
	 * @var boolean
	 */
	protected static $onKernelTerminateCalled = [];
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($app, $environment, $debug);
	}
	
	/**
	 * Data provider for test_sanitizeKernelName().
	 *
	 * @return array
	 */
	public function provide_test_sanitizeKernelName_data()
	{
		return [
			'\'foo\'' => [
				'foo',
				'foo',
				null
			],
			'\'foo_bar\'' => [
				'foo_bar',
				'foo_bar',
				null
			],
			'\'1foo_bar\'' => [
				'_1foo_bar',
				'1foo_bar',
				null
			],
			'\'$foo_bar\'' => [
				'foo_bar',
				'$foo_bar',
				null
			],
		];
	}
	
	/**
	 * @covers ::sanitizeKernelName()
	 * @dataProvider provide_test_sanitizeKernelName_data
	 * @param string $expected Expected sanitized kernel name
	 * @param string $kernelName Input kernel name
	 * @testdox sanitizeKernelName() returns correctly sanitized kernel name for
	 */
	public function test_sanitizeKernelName($expected, $kernelName)
	{
		$this->assertEquals($expected, BootKernel::sanitizeKernelName($kernelName));
	}

	/**
	 * Data provider for test_camelizeKernelName().
	 *
	 * @return array
	 */
	public function provide_test_camelizeKernelName_data()
	{
		return [
			'\'foo\'' => [
				'Foo',
				'foo',
				null
			],
			'\'foo_bar\'' => [
				'FooBar',
				'foo_bar',
				null
			],
			'\'1foo_bar\'' => [
				'_1fooBar',
				'1foo_bar',
				null
			],
			'\'$foo_bar\'' => [
				'FooBar',
				'$foo_bar',
				null
			],
		];
	}
	
	/**
	 * @covers ::camelizeKernelName()
	 * @dataProvider provide_test_camelizeKernelName_data
	 * @param string $expected Expected camelized kernel name
	 * @param string $kernelName Input kernel name
	 * @testdox camelizeKernelName() returns correctly camelized kernel name for
	 */
	public function test_camelizeKernelName($expected, $kernelName)
	{
		$this->assertEquals($expected, BootKernel::camelizeKernelName($kernelName));
	}
	
	/**
	 * @covers ::getName()
	 * @testdox getName() returns the correct kernel name
	 */
	public function test_getName()
	{
		// Check that getName() returns 'boot'
		$this->assertEquals('boot', self::$kernel->getName());
	}
	
	/**
	 * @covers ::getCacheDir()
	 * @testdox getCacheDir() returns the correct path
	 */
	public function test_getCacheDir()
	{
		$expected = dirname(self::$kernel->getRootDir()) . '/var/cache/boot/test';
		
		// Check that getCacheDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getCacheDir());
		
		// Check that getCacheDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'cacheDir', self::$kernel);
	}
	
	/**
	 * @covers ::getLogDir()
	 * @testdox getLogDir() returns the correct path
	 */
	public function test_getLogDir()
	{
		$expected = dirname(self::$kernel->getRootDir()) . '/var/logs/boot';
		
		// Check that getLogDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getLogDir());
		
		// Check that getLogDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'logDir', self::$kernel);
	}
	
	/**
	 * @covers ::registerBundles()
	 * @testdox registerBundles() returns the correct bundles
	 */
	public function test_registerBundles()
	{
		// Get registered bundles
		$bundles = self::$kernel->registerBundles();
		
		// Check that registerBundles() returned the correct number of bundles
		$this->assertEquals(4, count($bundles));
		
		// Check the returned bundles are instances of the correct classes
		$this->assertEquals(FrameworkBundle::class, get_class($bundles[0]));
		$this->assertEquals(MonologBundle::class, get_class($bundles[1]));
		$this->assertEquals(MotanaMultikernelBundle::class, get_class($bundles[2]));
		$this->assertEquals(TwigBundle::class, get_class($bundles[3]));
	}

	/**
	 * @covers ::useAppCache()
	 * @testdox useAppCache() sets the useAppCache property
	 */
	public function test_useAppCache()
	{
		// Check the appCache property is initialized as null
		$this->assertAttributeEquals(null, 'useAppCache', self::$kernel);
		
		// Check the appCache property is set correctly
		self::$kernel->useAppCache(true);
		$this->assertAttributeEquals(true, 'useAppCache', self::$kernel);

		// Check that setAppCache() converts the input value to boolean
		self::$kernel->useAppCache(1);
		$this->assertAttributeEquals(true, 'useAppCache', self::$kernel);
	}
	
	/**
	 * @covers ::loadKernelData()
	 * @testdox loadKernelData() loads kernel data
	 */
	public function test_loadKernelData()
	{
		$data = [
			'app' => [
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			],
		];
		
		$this->getFs()->mkdir(self::$kernel->getCacheDir());
		
		// Check the kernels property contains an empty array before loading kernel data
		$this->assertAttributeEquals([], 'kernels', self::$kernel);
		
		// Check the kernels property contains correct data after loading
		$this->callMethod(self::$kernel, 'loadKernelData');
		$this->assertAttributeEquals($data, 'kernels', self::$kernel);
		
		// Touch the kernel cache file
		$class = new \ReflectionClass(self::$kernel);
		self::getFs()->touch($file = self::$kernel->getCacheDir() . '/kernels.php', $time = time() - 3600);
		clearstatcache();
		
		// Reset the kernels property
		$this->writeAttribute(self::$kernel, 'kernels', []);
		
		// Check the kernels property contains correct data after loading
		$this->callMethod(self::$kernel, 'loadKernelData');
		$this->assertAttributeEquals($data, 'kernels', self::$kernel);
		
		// Check the kernel cache file has been updated
		$this->assertGreaterThan($time, filemtime($file));
	}
	
	/**
	 * @covers ::getKernelData()
	 * @testdox getKernelData() returns data for a kernel
	 */
	public function test_getKernelData()
	{
		$data = [
			'app' => [
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			],
		];
		
		$this->writeAttribute(self::$kernel, 'kernels', $data);
		
		// Check that getKernelData() returns NULL for invalid kernel names
		$this->assertNull($this->callMethod(self::$kernel, 'getKernelData', 'invalid'));
		
		// Check that getKernelData() returns data for a kernel
		$this->assertEquals($data['app'], $this->callMethod(self::$kernel, 'getKernelData', 'app'));
	}
	
	/**
	 * @covers ::loadKernel()
	 * @testdox loadKernel() loads existing kernels
	 */
	public function test_loadKernel()
	{
		// Check that loadKernel() returns null for invalid kernel names
		$this->assertNull($this->callMethod(self::$kernel, 'loadKernel', 'invalid'));
		
		// Check that loadKernel() returns the boot kernel when asked for it
		$this->assertSame(self::$kernel, $this->callMethod(self::$kernel, 'loadKernel', 'boot'));
		
		// Check that loadKernel() returns a cached result if available
		$this->assertSame(self::$kernel, $this->callMethod(self::$kernel, 'loadKernel', 'boot'));
		
		$app = $this->callMethod(self::$kernel, 'loadKernel', 'app');
		
		// Check that the kernel has been booted
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check that loadKernel() returns an instance of the correct class
		$this->assertEquals('AppKernel', get_class($app));
	}
	
	/**
	 * @covers ::loadKernel()
	 * @testdox loadKernel() returns a HttpCache instance when useAppCache is TRUE
	 */
	public function test_loadKernel_with_AppCache()
	{
		self::$kernel->useAppCache(true);
		
		// Check that loadKernel() instantiates the cache class
		$app = $this->callMethod(self::$kernel, 'loadKernel', 'app');
		
		// Check that the kernel has been booted
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check that loadKernel() returns an instance of the correct class
		$this->assertEquals('AppCache', get_class($app));
	}
	
	/**
	 * @covers ::getKernels()
	 * @testdox getKernels() returns correct kernels
	 */
	public function test_getKernels()
	{
		$kernels = self::$kernel->getKernels();
		
		// Check that the kernel has been booted
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check that getKernels() returns the correct array keys
		$this->assertEquals([ 'boot', 'app' ], array_keys($kernels));
		
		// Check the kernels are instances of the correct classes
		$this->assertEquals('BootKernel', get_class($kernels['boot']));
		$this->assertEquals('AppKernel', get_class($kernels['app']));
	}
	
	/**
	 * @covers ::getKernel()
	 * @testdox getKernel() returns correct kernels
	 */
	public function test_getKernel()
	{
		// Check that getKernel() returns the boot kernel when asked for it
		$this->assertEquals('BootKernel', get_class(self::$kernel->getKernel('boot')));
		
		// Check that getKernel() returns the app kernel when asked for it
		$this->assertEquals('AppKernel', get_class(self::$kernel->getKernel('app')));
	}
	
	/**
	 * @covers ::requestFactory()
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage A kernel must be loaded before BootKernel::requestFactory can be used.
	 * @testdox requestFactory() throws RuntimeException when no kernel is loaded
	 */
	public function test_requestFactory_error()
	{
		$server = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		// Check that requestFactory throws an exception before loadKernel() has been called
		self::$kernel->requestFactory($_GET, $_REQUEST, [], [], [], $server);
	}
	
	/**
	 * @covers ::requestFactory()
	 * @testdox requestFactory() creates a BootKernelRequest instance
	 */
	public function test_requestFactory()
	{
		$server = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		self::$kernel->getKernel('boot');
		$request = self::$kernel->requestFactory($_GET, $_REQUEST, [], [], [], $server);

		// Check the returned request is an instance of the correct class
		$this->assertInstanceOf(BootKernelRequest::class, $request);
		
		// Check that getBaseUrl() returns the correct path
		$this->assertEquals('/web', $request->getBaseUrl());
	}
	
	/**
	 * @covers ::requestFactory()
	 * @testdox requestFactory() creates a BootKernelRequest instance with AppCache
	 */
	public function test_requestFactory_with_AppCache()
	{
		$GLOBALS['BootKernelTest_loadKernel'] = 1;
		self::$kernel->useAppCache(true);
		
		$kernel = self::$kernel;
		$server = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		self::$kernel->getKernel('app');
		$request = self::$kernel->requestFactory($_GET, $_REQUEST, [], [], [], $server);
		
		// Check the returned request is an instance of the correct class
		$this->assertInstanceOf(BootKernelRequest::class, $request);
		
		// Check that getBaseUrl() returns the correct path
		$this->assertEquals('/web/app', $request->getBaseUrl());
		
		unset($GLOBALS['BootKernelTest_loadKernel']);
	}
	
	/**
	 * @covers ::getRequest()
	 * @testdox getRequest() returns the correct request
	 */
	public function test_getRequest()
	{
		$kernel = self::$kernel;
		$server = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		// Check that getRequest() returns null before getKernel() is called
		$this->assertNull(self::$kernel->getRequest());
		
		self::$kernel->getKernel('app');
		$kernel->requestFactory($_GET, $_REQUEST, [], [], [], $server);
		
		// Check that getRequest() returns the correct request
		$request = self::$kernel->getRequest();
		$this->assertEquals(BootKernelRequest::class, get_class($request));
		
		// Check the request returns the correct base url
		$this->assertEquals('/web/app', $request->getBaseUrl());
	}
	
	/**
	 * @covers ::boot()
	 * @testdox boot() actually boots the kernel
	 */
	public function test_boot()
	{
		$data = [
			'app' => [
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			],
		];
		
		// Check the kernels property is an empty array
		$this->assertAttributeEquals([], 'kernels', self::$kernel);
		
		// Check that boot() just returns when the booted property is true
		$this->writeAttribute(self::$kernel, 'booted', true);
		self::$kernel->boot();
		
		// Check the kernels property is still an empty array
		$this->assertAttributeEquals([], 'kernels', self::$kernel);
		
		// Now reset the booted property and propertly boot the kernel
		$this->writeAttribute(self::$kernel, 'booted', false);
		self::$kernel->boot();
		
		// Check the kernel is propertly booted
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check the kernels property is now initialized correctly
		$this->assertAttributeEquals($data, 'kernels', self::$kernel);
	}
	
	/**
	 * @covers ::getKernelFromRequest()
	 * @testdox getKernelFromRequest() returns correct matches
	 */
	public function test_getKernelFromRequest()
	{
		$server = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		// Check that getKernelFromRequest() returns the correct kernel name
		$this->assertEquals('app', $this->callMethod(self::$kernel, 'getKernelFromRequest',
			new Request($_GET, $_REQUEST, [], [], [], $server)));
		
		// Check that getKernelFromRequest() booted the kernel
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check that getKernelFromRequest() returns NULL when no match is found
		$this->assertNull($this->callMethod(self::$kernel, 'getKernelFromRequest',
			new Request($_GET, $_REQUEST, [], [], [], array_merge($server, [
				'REQUEST_URI' => '/web/foobar/controller/action',
			]))
		));
	}
	
	/**
	 * @covers ::getKernelFromRequest()
	 * @testdox getKernelFromRequest() returns correct matches for an URL without trailing slash
	 */
	public function test_getKernelFromRequest_without_trailing_slash()
	{
		$server = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		// Check that getKernelFromRequest() returns the correct kernel name
		$this->assertEquals('app', $this->callMethod(self::$kernel, 'getKernelFromRequest',
			new Request($_GET, $_REQUEST, [], [], [], $server)));
		
		// Check that getKernelFromRequest() booted the kernel
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
	}
	
	/**
	 * @covers ::handle()
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @expectedExceptionMessage No route found for "GET /controller/action"
	 * @testdox handle() throws NotFoundHttpException when no route is found
	 */
	public function test_handle_with_no_route_found()
	{
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		
		// Check the kernel throws an exception because it has no routes
		$response = self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER), HttpKernelInterface::MASTER_REQUEST, false);
	}
	
	/**
	 * @covers ::handle()
	 * @testdox handle() delegates requests to kernels
	 */
	public function test_handle_delegation()
	{
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		
		// Check the kernel throws an exception because it has no routes
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
	}
	
	/**
	 * @covers ::handle()
	 * @testdox handle() delegates requests to kernels for an URL without trailing slash
	 */
	public function test_handle_delegation_without_trailing_slash()
	{
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		
		// Check the kernel throws an exception because it has no routes
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
	}
	
	/**
	 * @covers ::handle()
	 * @kernelDebug true
	 * @kernelRebuild true
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 * @testdox handle() sets startTime property in debug mode
	 */
	public function test_handle_sets_startTime()
	{
		$this->setUp(null, 'test', true);
		
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		
		// Check the kernel throws an exception because it has no routes
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
		
		// Check the boot kernel has the same start time the app kernel has
		$this->assertEquals($this->readAttribute(self::$kernel, 'startTime'), $this->readAttribute($instances['app'], 'startTime'));
	}
	
	/**
	 * @covers ::handle()
	 * @testdox handle() delegates requests to default kernel
	 */
	public function test_handle_delegation_with_default_kernel()
	{
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		
		// Check the kernel throws an exception because it has no routes
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
	}

	/**
	 * @covers ::handle()
	 * @kernelDebug true
	 * @testdox handle() delegates requests to default kernel and sets startTime property in debug mode
	 */
	public function test_handle_delegation_with_default_kernel_in_debug_mode()
	{
		$this->setUp(null, 'test', true);
		
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		
		// Check the kernel throws an exception because it has no routes
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
		
		// Check the boot kernel has the same start time the app kernel has
		$this->assertEquals($this->readAttribute(self::$kernel, 'startTime'), $this->readAttribute($instances['app'], 'startTime'));
	}
	
	/**
	 * @covers ::handle()
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @expectedExceptionMessage Unable to load the default kernel. Did you forget to set the motana_multikernel.default in config.yml?
	 * @testdox handle() throws NotFoundHttpException when a non-existing default kernel is configured
	 */
	public function test_handle_not_existing_default_kernel()
	{
		// Boot the kernel
		self::$kernel->boot();
		$container = self::$kernel->getContainer();
		
		// Change the motana.multikernel.default parameter to an invalid kernel name
		$parameters = array_merge($this->getObjectAttribute($container, 'parameters'), [
			'motana.multikernel.default' => 'invalid',
		]);
		$this->writeAttribute($container, 'parameters', $parameters);
		
		// Check the kernel throws an exception when no default kernel is available
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
	}
	
	/**
	 * @covers ::handle()
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @expectedExceptionMessage Unable to load the default kernel. Did you forget to set the motana_multikernel.default in config.yml?
	 * @testdox handle() throws NotFoundHttpException when no default kernel is configured
	 */
	public function test_handle_no_default_kernel()
	{
		// Boot the kernel
		self::$kernel->boot();
		$container = self::$kernel->getContainer();
		
		// Change the motana.multikernel.default parameter to an invalid kernel name
		$parameters = array_merge($this->getObjectAttribute($container, 'parameters'), [
			'motana.multikernel.default' => null,
		]);
		$this->writeAttribute($container, 'parameters', $parameters);
		
		// Check the kernel throws an exception when no default kernel is available
		$_SERVER = array_merge($_SERVER, [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		self::$kernel->handle(new Request($_GET, $_REQUEST, [], [], [], $_SERVER));
	}
	
	/**
	 * @covers ::terminate()
	 * @testdox terminate() sends the Console::TERMINATE event to all kernels
	 */
	public function test_terminate()
	{
		// Create a request and response for the test
		$request = new Request($_GET, $_REQUEST, [], [], [], [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		]);
		$response = new Response();
		
		// Check that terminate() just returns when the kernel is not booted
		self::$kernel->terminate($request, $response);
		$this->assertEmpty(self::$onKernelTerminateCalled);

		// Add an event listener for the KernelEvents::TERMINATE event on the boot kernel
		self::$kernel->boot();
		$container = self::$kernel->getContainer();
		$container->get('event_dispatcher')->addListener(KernelEvents::TERMINATE, [ $this, 'onBootKernelTerminate' ]);
		
		// Add an event listener for the KernelEvents::TERMINATE event on the app kernel
		$appKernel = self::$kernel->getKernel('app');
		$appKernel->boot();
		$container = $appKernel->getContainer();
		$container->get('event_dispatcher')->addListener(KernelEvents::TERMINATE, [ $this, 'onAppKernelTerminate' ]);
		
		// Check that terminate() just returns when there are no kernel instances
		$this->writeAttribute(self::$kernel, 'instances', []);
		self::$kernel->terminate($request, $response);
		$this->assertEmpty(self::$onKernelTerminateCalled);
		
		// Add an event listener for the KernelEvents::TERMINATE event on the app kernel
		self::$kernel->getKernel('boot');
		$appKernel = self::$kernel->getKernel('app');
		$appKernel->boot();
		$container = $appKernel->getContainer();
		$container->get('event_dispatcher')->addListener(KernelEvents::TERMINATE, [ $this, 'onAppKernelTerminate' ]);
		
		// Check that terminate() dispatches the KernelEvents::TERMINATE event to all kernels
		self::$kernel->terminate($request, $response);
		$this->assertEquals([
			'boot',
			'app',
		], self::$onKernelTerminateCalled);
	}
	
	/**
	 * Event listener for the KernelEvents::TERMINATE event.
	 */
	public function onBootKernelTerminate(Event $event, $eventName, EventDispatcherInterface $dispatcher)
	{
		self::$onKernelTerminateCalled[] = 'boot';
	}
	
	/**
	 * Event listener for the KernelEvents::TERMINATE event.
	 */
	public function onAppKernelTerminate(Event $event, $eventName, EventDispatcherInterface $dispatcher)
	{
		self::$onKernelTerminateCalled[] = 'app';
	}
}
