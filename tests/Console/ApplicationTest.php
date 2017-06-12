<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Console;

use Motana\Bundle\MultiKernelBundle\Console\Application;
use Motana\Bundle\MultiKernelBundle\Command\HelpCommand;
use Motana\Bundle\MultiKernelBundle\Command\ListCommand;
use Motana\Bundle\MultiKernelBundle\Test\ApplicationTestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Application
 */
class ApplicationTest extends ApplicationTestCase
{
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
		
		self::$application = new Application(self::$kernel);
	}
	
	/**
	 * @covers ::__construct()
	 */
	public function testConstructor()
	{
		$this->assertAttributeEquals(false, 'autoExit', self::$application);
	}
	
	/**
	 * @covers ::getName()
	 */
	public function testGetName()
	{
		$this->assertEquals('Motana Multi-Kernel App Console - Symfony', self::$application->getName());
	}
	
	/**
	 * @covers ::getDefaultCommands()
	 */
	public function testGetDefaultCommands()
	{
		$commands = $this->callMethod(self::$application, 'getDefaultCommands');
		
		// Check the method returned two commands
		$this->assertEquals(2, count($commands));
		
		// Check the two commands are instances of the correct classes
		$this->assertInstanceOf(HelpCommand::class, $commands[0]);
		$this->assertInstanceOf(ListCommand::class, $commands[1]);
	}
}
