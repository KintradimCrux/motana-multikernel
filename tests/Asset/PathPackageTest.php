<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Asset;

use Motana\Bundle\MultikernelBundle\Asset\PathPackage;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Asset\PathPackage
 * @testdox Motana\Bundle\MultikernelBundle\Asset\PathPackage
 */
class PathPackageTest extends TestCase
{
	/**
	 * @var PathPackage
	 */
	protected static $package;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$serverVars = [
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		];
		
		$stack = new RequestStack();
		$stack->push(new BootKernelRequest($_GET, $_REQUEST, [], [], [], $serverVars, null, 'app'));
		
		self::$package = new PathPackage('..', new EmptyVersionStrategy(), new RequestStackContext($stack));
	}
	
	/**
	 * @covers ::getBasePath()
	 * @testdox getBasePath() returns a path not containing '../'
	 */
	public function test_getBasePath()
	{
		$this->assertEquals('/web/', self::$package->getBasePath());
	}
}
