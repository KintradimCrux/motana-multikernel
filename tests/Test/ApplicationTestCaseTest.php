<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Test;

use Motana\Bundle\MultikernelBundle\Console\MultiKernelApplication;
use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;
use Motana\Bundle\MultikernelBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase
 */
class ApplicationTestCaseTest extends KernelTestCase
{
	/**
	 * @var ApplicationTestCase
	 */
	protected static $testCase;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = 'app', $environment = 'test', $debug = false)
	{
		self::$testCase = $this->createMock(ApplicationTestCase::class);
		self::$varDir = $this->callMethod(KernelTestCase::class, 'getVarDir', $type);
	}
	
	/**
	 * @covers ::setUp()
	 */
	public function testSetUpBootKernel()
	{
		$this->callMethod(self::$testCase, 'setUp');
		
		$this->assertInstanceOf(MultiKernelApplication::class, $this->readAttribute(ApplicationTestCase::class, 'application'));
	}
	
	/**
	 * @covers ::setUp()
	 * @depends testSetUpBootKernel
	 */
	public function testSetUpAppKernel()
	{
		$this->callMethod(self::$testCase, 'setUp', 'working', 'app');
		
		$this->assertInstanceOf(Application::class, $this->readAttribute(ApplicationTestCase::class, 'application'));
	}
}
