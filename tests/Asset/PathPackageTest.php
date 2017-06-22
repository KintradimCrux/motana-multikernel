<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Asset;

use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\RequestStack;

use Motana\Bundle\MultikernelBundle\Asset\PathPackage;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernelRequest;
use Motana\Bundle\MultikernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Asset\PathPackage
 */
class PathPackageTest extends TestCase
{
	/**
	 * @var PathPackage
	 */
	protected static $package;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$serverVars = array(
			'BASE' => '/web',
			'PHP_SELF' => '/web/app.php',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '/web/foobar/controller/action',
			'SCRIPT_FILENAME' => '/home/user/public_html/web/app.php',
			'SCRIPT_NAME' => '/web/app.php',
		);
		
		$stack = new RequestStack();
		$stack->push(new BootKernelRequest($_GET, $_REQUEST, array(), array(), array(), $serverVars, null, 'app'));
		
		self::$package = new PathPackage('..', new EmptyVersionStrategy(), new RequestStackContext($stack));
	}
	
	/**
	 * @covers ::getBasePath()
	 */
	public function testGetBasePath()
	{
		$this->assertEquals('/web/', self::$package->getBasePath());
	}
}
