<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Descriptor;

use Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\TestCase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor
 * @testdox Motana\Bundle\MultikernelBundle\Console\Descriptor\Descriptor
 */
class DescriptorTest extends TestCase
{
	/**
	 * The project directory.
	 *
	 * @var string
	 */
	protected static $projectDir;
	
	/**
	 * The descriptor to test.
	 *
	 * @var Descriptor
	 */
	protected static $descriptor;
	
	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		// Check the listener initialized the test environment
		if ( ! getenv('__MULTIKERNEL_FIXTURE_DIR')) {
			throw new \Exception(sprintf('The fixtures directory for the tests was not created. Did you forget to add the listener "%s" to your phpunit.xml?', TestListener::class));
		}
		
		// Set the fixtures directory for the tests
		self::$projectDir = getenv('__MULTIKERNEL_FIXTURE_DIR') . '/project';
		
		$container = new ContainerBuilder();
		$container->setParameter('kernel.project_dir', self::$projectDir);
		
		self::$descriptor = $this->getMockForAbstractClass(Descriptor::class);
		self::$descriptor->setContainer($container);
	}
	
	/**
	 * @covers ::makePathRelative()
	 * @testdox makePathRelative() returns a path relative to project directory
	 */
	public function test_makePathRelative()
	{
		// Check the returned path is correct
		$this->assertEquals('./tests/Controller', $this->callMethod(self::$descriptor, 'makePathRelative', self::$projectDir . '/tests/Controller'));
	}
	
	/**
	 * @covers ::getProcessedHelp()
	 * @testdox getProcessedHelp() removes 'php' from command names
	 */
	public function test_getProcessedHelp()
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = self::$projectDir . '/bin/console';
		
		// Create a new command
		$command = new Command('test');
		
		// Set the command help
		$command->setHelp(<<<EOH
%command.name%
php %command.full_name%
EOH
		);
		
		// Check the output is correct
		$expected = <<<EOH
test
./bin/console test
EOH;
		$this->assertEquals($expected, $this->callMethod(self::$descriptor, 'getProcessedHelp', $command));
	}
}
