<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\HttpKernel\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler\AddKernelsToCachePass;
use Motana\Bundle\MultiKernelBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\DependencyInjection\Compiler\AddKernelsToCachePass
 */
class AddKernelsToCachePassTest extends KernelTestCase
{
	/**
	 * @var AddKernelsToCachePass
	 */
	protected static $pass;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
		
		self::$pass = new AddKernelsToCachePass(self::$kernel);
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertAttributeSame(self::$kernel, 'kernel', self::$pass);
	}
	
	/**
	 * @covers ::process()
	 */
	public function testProcess()
	{
		$data = array(
			'app' => array(
				'kernel' => 'app/AppKernel.php',
				'cache' => 'app/AppCache.php',
			),
		);
		
		$this->getFs()->mkdir(self::$kernel->getCacheDir());
		
		$container = new ContainerBuilder();
		self::$pass->process($container);
		
		// Check the cache file has the correct content
		$this->assertEquals(sprintf('<?php return %s;', var_export($data, true)),
			file_get_contents(self::$kernel->getCacheDir() . '/kernels.php'));
	}
}
