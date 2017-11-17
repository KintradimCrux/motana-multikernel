<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Generator\Model;

use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Generator\Model\App
 * @testdox Motana\Bundle\MultikernelBundle\Generator\Model\App
 */
class AppTest extends TestCase
{
	/**
	 * The model to test.
	 *
	 * @var App
	 */
	protected static $model;
	
	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 *
	 * @param string|null $kernelName Kernel name for the app model
	 * @return void
	 */
	protected function setUp($kernelName = null)
	{
		switch ($kernelName)
		{
			// Kernel name 'foo_bar'
			case 'foo_bar':
				self::$model = new App(
					__DIR__,
					'foo_bar',
					true,
					true,
					'FooBarBundle',
					'FooBarBundle',
					__DIR__ . '/src',
					'annotation',
					false
				);
				break;
				
			// Kernel name '1foo_bar'
			case '1foo_bar':
				self::$model = new App(
					__DIR__,
					'1foo_bar',
					true,
					true,
					'_1fooBarBundle',
					'_1fooBarBundle',
					__DIR__ . '/src',
					'annotation',
					false
				);
				break;
				
			// Kernel name 'foo'
			default:
				self::$model = new App(
					__DIR__,
					'foo',
					true,
					true,
					'FooBundle',
					'FooBundle',
					__DIR__ . '/src',
					'annotation',
					false
				);
				break;
		}
	}

	/**
	 * Data provider for test_constructor(), test_getKernelName(),
	 * test_getKernelClassName() and test_getCacheClassName().
	 *
	 * @return array
	 */
	public function provide_kernel_names()
	{
		return [
			'kernel name \'foo\'' => [
				'foo',
				null,
				null
			],
			'kernel name \'foo_bar\'' => [
				'foo_bar',
				null,
				null
			],
			'kernel name \'1foo_bar\'' => [
				'1foo_bar',
				null,
				null
			],
		];
	}
	
	/**
	 * @covers ::__construct()
	 * @dataProvider provide_kernel_names
	 * @param string|null $kernelName Kernel name for the app model
	 * @testdox __construct() sets up properties correctly for
	 */
	public function test_constructor($kernelName = null)
	{
		// Set up the app model with the specified kernel name
		$this->setUp($kernelName);
		
		// Camelize the kernel name
		$kernelName = BootKernel::sanitizeKernelName($kernelName);
		$kernelCamel = BootKernel::camelizeKernelName($kernelName);
		
		// Check the constructor sets up properties correctly
		$this->assertEquals($kernelName, $this->readAttribute(self::$model, 'kernelName'));
		$this->assertEquals(__DIR__, $this->readAttribute(self::$model, 'projectDirectory'));
		$this->assertTrue($this->readAttribute(self::$model, 'generateBundle'));
		$this->assertFalse($this->readAttribute(self::$model, 'generateMicrokernel'));
		$this->assertTrue($this->readAttribute(self::$model, 'multikernel'));
		
		// Check the parent constructor has been called
		$this->assertEquals($kernelCamel . 'Bundle', $this->readAttribute(self::$model, 'namespace'));
		$this->assertEquals($kernelCamel . 'Bundle', $this->readAttribute(self::$model, 'name'));
		$this->assertEquals('annotation', $this->readAttribute(self::$model, 'configurationFormat'));
		$this->assertFalse($this->readAttribute(self::$model, 'isShared'));
		$this->assertEquals(__DIR__ . '/tests', $this->readAttribute(self::$model, 'testsDirectory'));
	}
	
	/**
	 * @covers ::getProjectDirectory()
	 * @testdox getProjectDirectory() returns the correct path
	 */
	public function test_getProjectDirectory()
	{
		// Check the correct directory is returned
		$this->assertEquals(__DIR__, self::$model->getProjectDirectory());
	}
	
	/**
	 * @covers ::getAppDirectory()
	 * @testdox getAppDirectory() returns the correct path
	 */
	public function test_getAppDirectory()
	{
		// Check the correct directory is returned
		$this->assertEquals(__DIR__ . '/apps/foo', self::$model->getAppDirectory());
	}
	
	/**
	 * @covers ::getKernelName()
	 * @dataProvider provide_kernel_names
	 * @param string|null $kernel Kernel name for the app model
	 * @testdox getKernelName() returns the correct kernel name
	 */
	public function test_getKernelName($kernelName = null)
	{
		// Set up the app model with the specified kernel name
		$this->setUp($kernelName);
		
		// Sanitize the kernel name
		$kernelName = BootKernel::sanitizeKernelName($kernelName);
		
		// Check the correct kernel name is returned
		$this->assertEquals($kernelName, self::$model->getKernelName());
	}
	
	/**
	 * @covers ::getKernelClassName()
	 * @dataProvider provide_kernel_names
	 * @param string|null $kernel Kernel name for the app model
	 * @testdox getKernelClassName() returns the correct kernel class name
	 */
	public function test_getKernelClassName($kernelName = null)
	{
		// Set up the app model with the specified kernel name
		$this->setUp($kernelName);
		
		// Camelize the kernel name
		$kernelCamel = Container::camelize($kernelName);
		if (ctype_digit($kernelName[0])) {
			$kernelCamel = '_' . $kernelCamel;
		}
		
		// Check the correct class name is returned
		$this->assertEquals($kernelCamel . 'Kernel', self::$model->getKernelClassName());
	}
	
	/**
	 * @covers ::getCacheClassName()
	 * @dataProvider provide_kernel_names
	 * @param string|null $kernel Kernel name for the app model
	 * @testdox getCacheClassName() returns the correct cache class name
	 */
	public function test_getCacheClassName($kernelName = null)
	{
		// Set up the app model with the specified kernel name
		$this->setUp($kernelName);
		
		// Camelize the kernel name
		$kernelCamel = Container::camelize($kernelName);
		if (ctype_digit($kernelName[0])) {
			$kernelCamel = '_' . $kernelCamel;
		}
		
		// Check the correct class name is returned
		$this->assertEquals($kernelCamel . 'Cache', self::$model->getCacheClassName());
	}
	
	/**
	 * @covers ::shouldGenerateBundle()
	 * @testdox shouldGenerateBundle() returns the value of the generateBundle property
	 */
	public function test_shouldGenerateBundle()
	{
		// Check the correct value is returned
		$this->assertTrue(self::$model->shouldGenerateBundle());
		
		// Change the property to FALSE
		$this->writeAttribute(self::$model, 'generateBundle', false);
		
		// Check the correct value is returned
		$this->assertFalse(self::$model->shouldGenerateBundle());
	}
	
	/**
	 * @covers ::shouldGenerateMicrokernel()
	 * @testdox shouldGenerateMicrokernel returns the value of the generateMicrokernel property
	 */
	public function test_shouldGenerateMicrokernel()
	{
		// Check the correct value is returned
		$this->assertFalse(self::$model->shouldGenerateMicrokernel());
		
		// Change the property to TRUE
		$this->writeAttribute(self::$model, 'generateMicrokernel', true);
		
		// Check the correct value is returned
		$this->assertTrue(self::$model->shouldGenerateMicrokernel());
	}
	
	/**
	 * @covers ::shouldGenerateMultikernel()
	 * @testdox shouldGenerateMultikernel returns the value of the multikernel property
	 */
	public function test_shouldGenerateMultikernel()
	{
		// Check the correct value is returned
		$this->assertTrue(self::$model->shouldGenerateMultikernel());
		
		// Change the property to FALSE
		$this->writeAttribute(self::$model, 'multikernel', false);
		
		// Check the correct value is returned
		$this->assertFalse(self::$model->shouldGenerateMultikernel());
	}
}
