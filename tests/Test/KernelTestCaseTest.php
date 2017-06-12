<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Test;

use Motana\Bundle\MultiKernelBundle\HttpKernel\Kernel;
use Motana\Bundle\MultiKernelBundle\Test\KernelTestCase;
use Motana\Bundle\MultiKernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Test\KernelTestCase
 */
class KernelTestCaseTest extends TestCase
{
	/**
	 * @var KernelTestCase
	 */
	protected static $testCase;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$testCase = $this->createMock(KernelTestCase::class);
		
		$this->callMethod(self::$testCase, 'setUp');
	}
	
	/**
	 * @covers ::setUp()
	 */
	public function testSetUp()
	{
		$fixturesDir = $this->readAttribute(KernelTestCase::class, 'fixturesDir');
		
		$this->assertInstanceOf(Kernel::class, $this->readAttribute(KernelTestCase::class, 'kernel'));
		
		$this->assertAttributeEquals($fixturesDir . '/kernels/working/var', 'varDir', KernelTestCase::class);
	}
	
	/**
	 * @covers ::tearDownVarDir()
	 */
	public function testTearDownVarDir()
	{
		$varDir = $this->readAttribute(KernelTestCase::class, 'varDir');
		
		self::getFs()->mkdir($varDir . '/cache/test/boot');
		
		KernelTestCase::tearDownVarDir();
		
		$this->assertFalse(is_dir($varDir));
	}
	
	/**
	 * Data provider for testGetFixturesDir().
	 * 
	 * @return array
	 */
	public function provide_testGetFixturesDir_data()
	{
		$fixturesDir = $this->readAttribute(KernelTestCase::class, 'fixturesDir');
		
		return array(
			array($fixturesDir . '/kernels/broken/apps', 'broken', null),
			array($fixturesDir . '/kernels/broken/apps/brokenCache', 'broken', 'brokenCache'),
			array($fixturesDir . '/kernels/broken/apps/brokenKernel', 'broken', 'brokenKernel'),
			array($fixturesDir . '/kernels/working/apps', 'working', null),
			array($fixturesDir . '/kernels/working/apps/app', 'working', 'app'),
		);
	}
	
	/**
	 * @covers ::getFixturesDir()
	 * @dataProvider provide_testGetFixturesDir_data
	 * @param string $expectedPath Expected path
	 * @param string $type Fixture type (broken | working)
	 * @param string $app App subdirectory name (if not loading a BootKernel)
	 */
	public function testGetFixturesDir($expectedPath, $type, $app)
	{
		$this->assertEquals($expectedPath, $this->callMethod(KernelTestCase::class, 'getFixturesDir', $type, $app));
	}
	
	/**
	 * Data provider for testGetVarDir().
	 * 
	 * @return array
	 */
	public function provide_testGetVarDir_data()
	{
		$fixturesDir = $this->readAttribute(KernelTestCase::class, 'fixturesDir');
		
		return array(
			array($fixturesDir . '/kernels/broken/var', 'broken'),
			array($fixturesDir . '/kernels/working/var', 'working'),
		);
	}
	
	/**
	 * @covers ::getVarDir()
	 * @dataProvider provide_testGetVarDir_data()
	 */
	public function testGetVarDir($expectedPath, $type)
	{
		$this->assertEquals($expectedPath, $this->callMethod(KernelTestCase::class, 'getVarDir', $type));
	}
	
	/**
	 * @covers ::findFixtureKernel()
	 */
	public function testFindFixtureKernel()
	{
		$fixturesDir = $this->callMethod(KernelTestCase::class, 'getFixturesDir', 'working', 'app');
		
		$this->assertEquals($fixturesDir . '/AppKernel.php', $this->callMethod(KernelTestCase::class, 'findFixtureKernel', $fixturesDir));
	}
	
	/**
	 * @covers ::findFixtureCache()
	 */
	public function testFindFixtureCache()
	{
		$fixturesDir = $this->callMethod(KernelTestCase::class, 'getFixturesDir', 'working', 'app');
		
		$this->assertEquals($fixturesDir . '/AppCache.php', $this->callMethod(KernelTestCase::class, 'findFixtureCache', $fixturesDir));
	}
	
	/**
	 * @covers ::loadFixtureKernel()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cannot find fixture kernel class for type='broken', app='brokenApp'
	 */
	public function testLoadFixtureKernelChecksClassExists()
	{
		$this->callMethod(KernelTestCase::class, 'loadFixtureKernel', 'broken', 'brokenApp', 'test', false);
	}
	
	/**
	 * @covers ::loadFixtureKernel()
	 * @depends testLoadFixtureKernelChecksClassExists
	 */
	public function testLoadFixtureKernel()
	{
		$kernel = $this->callMethod(KernelTestCase::class, 'loadFixtureKernel', 'working', 'app', 'test', false);

		// Check the AppKernel and AppCache classes exist
		$this->assertTrue(class_exists('AppKernel'));
		$this->assertTrue(class_exists('AppCache'));
		
		// Check the returned kernel is an instance of the correct class
		$this->assertInstanceOf(Kernel::class, $kernel);
	}
}
