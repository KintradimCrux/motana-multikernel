<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\HttpKernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler\AddKernelsToCachePass;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest;
use Motana\Bundle\MultikernelBundle\MotanaMultikernelBundle;
use Motana\Bundle\MultikernelBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
 */
class BootKernelTest extends KernelTestCase
{
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Test\KernelTestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
	}
	
	/**
	 * @covers ::getName()
	 */
	public function testGetName()
	{
		// Check that getName() returns 'boot'
		$this->assertEquals('boot', self::$kernel->getName());
	}
	
	/**
	 * @covers ::getCacheDir()
	 */
	public function testGetCacheDir()
	{
		$expected = dirname(self::$kernel->getRootDir()) . '/var/cache/boot/test';
		
		// Check that getCacheDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getCacheDir());
		
		// Check that getCacheDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'cacheDir', self::$kernel);
	}
	
	/**
	 * @covers ::getLogDir()
	 */
	public function testGetLogDir()
	{
		$expected = dirname(self::$kernel->getRootDir()) . '/var/logs/boot';
		
		// Check that getLogDir() returns the correct path
		$this->assertEquals($expected, self::$kernel->getLogDir());
		
		// Check that getLogDir() sets the cacheDir property
		$this->assertAttributeEquals($expected, 'logDir', self::$kernel);
	}
	
	/**
	 * @covers ::buildContainer()
	 */
	public function testBuildContainer()
	{
		$this->callMethod(self::$kernel, 'initializeBundles');
		
		$container = $this->callMethod(self::$kernel, 'buildContainer');
		/** @var ContainerBuilder $container */
		$passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
		
		// Check the AddKernelsToCachePass has been added
		$this->assertInstanceOf(AddKernelsToCachePass::class, end($passes));
	}
	
	/**
	 * @covers ::registerBundles()
	 */
	public function testRegisterBundles()
	{
		$bundles = self::$kernel->registerBundles();
		
		$expectedCount = 2 + (int) class_exists('Symfony\\Bundle\\WebServerBundle\\WebServerBundle');
		
		// Check that registerBundles() returned 2 bundles
		$this->assertEquals($expectedCount, count($bundles));
		
		// Check the returned bundles are instances of the correct classes
		$this->assertEquals(FrameworkBundle::class, get_class($bundles[0]));
		$this->assertEquals(MotanaMultikernelBundle::class, get_class($bundles[1]));
		
		if (class_exists('Symfony\\Bundle\\WebServerBundle\\WebServerBundle')) {
			$this->assertEquals('Symfony\\Bundle\\WebServerBundle\\WebServerBundle', get_class($bundles[2]));
		}
	}
	
	/**
	 * @covers ::useAppCache()
	 */
	public function testUseAppCache()
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
	 * @covers ::setKernelData()
	 */
	public function testSetKernelData()
	{
		$data = array(
			'app' => array(
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			),
		);
		
		$this->getFs()->mkdir(self::$kernel->getCacheDir());
		self::$kernel->setKernelData($data);
		
		// Check the cache file has the correct content
		$this->assertEquals(sprintf('<?php return %s;', var_export($data, true)),
			file_get_contents(self::$kernel->getCacheDir() . '/kernels.php'));
	}
	
	/**
	 * @covers ::loadKernelData()
	 * @depends testSetKernelData
	 */
	public function testLoadKernelData()
	{
		$data = array(
			'app' => array(
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			),
		);
		
		$this->getFs()->mkdir(self::$kernel->getCacheDir());
		self::$kernel->setKernelData($data);
		
		// Check the kernels property contains an empty array before loading kernel data
		$this->assertAttributeEquals(array(), 'kernels', self::$kernel);
		
		// Check the kernels property contains correct data after loading
		$this->callMethod(self::$kernel, 'loadKernelData');
		$this->assertAttributeEquals($data, 'kernels', self::$kernel);
	}
	
	/**
	 * @covers ::getKernelData()
	 * @depends testLoadKernelData
	 */
	public function testGetKernelData()
	{
		$data = array(
			'app' => array(
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			),
		);
		
		$this->writeAttribute(self::$kernel, 'kernels', $data);
		
		// Check that getKernelData() returns NULL for invalid kernel names
		$this->assertNull($this->callMethod(self::$kernel, 'getKernelData', 'invalid'));
		
		// Check that getKernelData() returns data for a kernel
		$this->assertEquals($data['app'], $this->callMethod(self::$kernel, 'getKernelData', 'app'));
	}
	
	/**
	 * @covers ::loadKernel()
	 * @depends testGetKernelData
	 */
	public function testLoadKernel()
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
	 * @depends testUseAppCache
	 * @depends testLoadKernel
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Kernel class "BrokenKernelKernel" does not exist. Did you name your kernel class correctly?
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testLoadKernelChecksKernelClass()
	{
		$this->setUp('broken');
		
		self::callMethod(self::$kernel, 'loadKernel', 'brokenKernel');
	}
	
	/**
	 * @covers ::loadKernel()
	 * @depends testLoadKernelChecksKernelClass
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Cache class "BrokenCacheCache" does not exist. Did you name your cache class correctly?
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testLoadKernelChecksCacheClass()
	{
		$this->setUp('broken');
		
		self::$kernel->useAppCache(true);
		self::callMethod(self::$kernel, 'loadKernel', 'brokenCache');
	}
	
	/**
	 * @covers ::loadKernel()
	 * @depends testUseAppCache
	 * @depends testLoadKernelChecksCacheClass
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testLoadKernelCache()
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
	 * @depends testLoadKernel
	 */
	public function testGetKernels()
	{
		$kernels = self::$kernel->getKernels();
		
		// Check that the kernel has been booted
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check that getKernels() returns the correct array keys
		$this->assertEquals(array('boot', 'app'), array_keys($kernels));
		
		// Check the kernels are instances of the correct classes
		$this->assertEquals('BootKernel', get_class($kernels['boot']));
		$this->assertEquals('AppKernel', get_class($kernels['app']));
	}
	
	/**
	 * @covers ::getKernel()
	 * @depends testLoadKernel
	 */
	public function testGetKernel()
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
	 */
	public function testRequestFactoryThrowsException()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check that requestFactory throws an exception before loadKernel() has been called
		self::$kernel->requestFactory($_GET, $_REQUEST, array(), array(), array(), $server);
	}
	
	/**
	 * @covers ::requestFactory
	 * @depends testRequestFactoryThrowsException
	 */
	public function testRequestFactory()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		self::$kernel->getKernel('boot');
		$request = self::$kernel->requestFactory($_GET, $_REQUEST, array(), array(), array(), $server);

		// Check the returned request is an instance of the correct class
		$this->assertInstanceOf(BootKernelRequest::class, $request);
		
		// Check that getBaseUrl() returns the correct path
		$this->assertEquals('/web/boot', $request->getBaseUrl());
	}
	
	/**
	 * @covers ::requestFactory()
	 * @depends testUseAppCache
	 * @depends testRequestFactory
	 */
	public function testRequestFactoryWithAppCache()
	{
		$GLOBALS['BootKernelTest_loadKernel'] = 1;
		self::$kernel->useAppCache(true);
		
		$kernel = self::$kernel;
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		self::$kernel->getKernel('app');
		$request = self::$kernel->requestFactory($_GET, $_REQUEST, array(), array(), array(), $server);
		
		// Check the returned request is an instance of the correct class
		$this->assertInstanceOf(BootKernelRequest::class, $request);
		
		// Check that getBaseUrl() returns the correct path
		$this->assertEquals('/web/app', $request->getBaseUrl());
		
		unset($GLOBALS['BootKernelTest_loadKernel']);
	}
	
	/**
	 * @covers ::getRequest()
	 */
	public function testGetRequest()
	{
		$kernel = self::$kernel;
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/boot/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check that getRequest() returns null before getKernel() is called
		$this->assertNull(self::$kernel->getRequest());
		
		self::$kernel->getKernel('app');
		$kernel->requestFactory($_GET, $_REQUEST, array(), array(), array(), $server);
		
		// Check that getRequest() returns the correct request
		$request = self::$kernel->getRequest();
		$this->assertEquals(BootKernelRequest::class, get_class($request));
		
		// Check the request returns the correct base url
		$this->assertEquals('/web/app', $request->getBaseUrl());
	}
	
	/**
	 * @covers ::boot()
	 */
	public function testBoot()
	{
		$data = array(
			'app' => array(
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			),
		);
		
		// Check the kernels property is an empty array
		$this->assertAttributeEquals(array(), 'kernels', self::$kernel);
		
		// Check that boot() just returns when the booted property is true
		$this->writeAttribute(self::$kernel, 'booted', true);
		self::$kernel->boot();
		
		// Check the kernels property is still an empty array
		$this->assertAttributeEquals(array(), 'kernels', self::$kernel);
		
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
	 * @depends testBoot
	 */
	public function testGetKernelFromRequest()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check that getKernelFromRequest() returns the correct kernel name
		$this->assertEquals('app', $this->callMethod(self::$kernel, 'getKernelFromRequest',
			new Request($_GET, $_REQUEST, array(), array(), array(), $server)));
		
		// Check that getKernelFromRequest() booted the kernel
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
		
		// Check that getKernelFromRequest() returns NULL when no match is found
		$this->assertNull($this->callMethod(self::$kernel, 'getKernelFromRequest',
			new Request($_GET, $_REQUEST, array(), array(), array(), array_merge($server,array(
				'REQUEST_URI' => '/web/foobar/controller/action',
			)))
		));
	}
	
	/**
	 * @covers ::getKernelFromRequest()
	 * @depends testGetKernelFromRequest
	 */
	public function testGetKernelFromRequestWithoutTrailingSlash()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check that getKernelFromRequest() returns the correct kernel name
		$this->assertEquals('app', $this->callMethod(self::$kernel, 'getKernelFromRequest',
			new Request($_GET, $_REQUEST, array(), array(), array(), $server)));
		
		// Check that getKernelFromRequest() booted the kernel
		$this->assertAttributeEquals(true, 'booted', self::$kernel);
	}
	
	/**
	 * @covers ::handle()
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @expectedExceptionMessage Unable to find the controller for path "/". The route is wrongly configured.
	 */
	public function testHandleThrowsException()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception because it has no routes
		self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server));
	}
	
	/**
	 * @covers ::handle()
	 * @depends testHandleThrowsException
	 */
	public function testHandle()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception because it has no routes
		try { self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server)); }
		catch (\Exception $e) { }
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
	}
	
	/**
	 * @covers ::handle()
	 * @depends testHandle
	 */
	public function testHandleWithoutTrailingSlash()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception because it has no routes
		try { self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server)); }
		catch (\Exception $e) { }
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
	}
	
	/**
	 * @covers ::handle()
	 * @depends testHandleThrowsException
	 */
	public function testHandleSetsStartTimeInDebugMode()
	{
		$this->setUp('working', null, 'test', true);
		
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/app/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception because it has no routes
		try { self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server)); }
		catch (\Exception $e) { }
		
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
	 * @depends testHandle
	 */
	public function testHandleDefaultKernel()
	{
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception because it has no routes
		try { self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server)); }
		catch (\Exception $e) { }
		
		// Check that calling handle() instantiated one kernel
		$instances = $this->getObjectAttribute(self::$kernel, 'instances');
		$this->assertEquals(1, count($instances));
		
		// Check the instantiated kernel is the correct one
		$this->assertTrue(isset($instances['app']));
		$this->assertEquals('AppKernel', get_class($instances['app']));
	}

	/**
	 * @covers ::handle()
	 * @depends testHandle
	 */
	public function testHandleDefaultKernelSetsStartTimeInDebugMode()
	{
		$this->setUp('working', null, 'test', true);
		
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception because it has no routes
		try { self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server)); }
		catch (\Exception $e) { }
		
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
	 */
	public function testHandleNoDefaultKernel()
	{
		self::$kernel->boot();
		$container = self::$kernel->getContainer();
		
		$parameters = array_merge($this->getObjectAttribute($container, 'parameters'), array(
			'motana.multikernel.default' => 'invalid',
		));
		$this->writeAttribute($container, 'parameters', $parameters);
		
		$server = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		// Check the kernel throws an exception when no default kernel is available
		self::$kernel->handle(new Request($_GET, $_REQUEST, array(), array(), array(), $server));
	}
	
	/**
	 * @covers ::terminate()
	 */
	public function testTerminate()
	{
		$kernel = self::$kernel;
		$request = new Request($_GET, $_REQUEST, array(), array(), array(), array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		));
		$response = new Response();
		
		// Check that terminate() just returns when the kernel is not booted
		$this->writeAttribute(self::$kernel, 'instances', array(
			'foo' => new \stdClass(),
		));
		self::$kernel->terminate($request, $response);
		
		// Check that terminate() just returns when there are no kernel instances
		$this->writeAttribute(self::$kernel, 'instances', array());
		self::$kernel->boot();
		self::$kernel->terminate($request, $response);
		
		$kernels = self::$kernel->getKernels();
		foreach ($kernels as $kernel) {
			$kernel->boot();
		}
		
		// Check that terminate() uses the request parameter before requestFactory() was called
		self::$kernel->terminate($request, $response);
		
		// Check that terminate() uses the request generated by requestFactory()
		self::$kernel->getKernel('app');
		self::$kernel->requestFactory($_GET, $_REQUEST, array(), array(), array(), array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		));
		self::$kernel->terminate($request, $response);
	}
}
