<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Console;

use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Command\HelpCommand;
use Motana\Bundle\MultikernelBundle\Command\ListCommand;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Application
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
	
	/**
	 * @covers ::get()
	 */
	public function testGet()
	{
		$this->assertInstanceOf(HelpCommand::class, self::$application->get('help'));
		$this->assertInstanceOf(ListCommand::class, self::$application->get('list'));
	}
	
	/**
	 * @covers ::get()
	 * @depends testGet
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "invalid" does not exist.
	 */
	public function testGetInvalidCommand()
	{
		self::$application->get('invalid');
	}
	
	/**
	 * @covers ::get()
	 * @depends testGetInvalidCommand
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "help" does not exist.
	 */
	public function testGetHiddenCommand()
	{
		/** @var Command $command */
		$command = self::$application->get('help');
		$command->setHidden(true);
		
		self::$application->get('help');
	}
	
	/**
	 * @covers ::find()
	 */
	public function testFind()
	{
		$this->assertInstanceOf(HelpCommand::class, self::$application->find('help'));
		$this->assertInstanceOf(ListCommand::class, self::$application->find('list'));
	}

	/**
	 * @covers ::get()
	 * @depends testGet
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage Command "invalid" is not defined.
	 */
	public function testFindInvalidCommand()
	{
		self::$application->find('invalid');
	}
	
	/**
	 * @covers ::get()
	 * @depends testFind
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "help" does not exist.
	 */
	public function testFindHiddenCommand()
	{
		/** @var Command $command */
		$command = self::$application->find('help');
		$command->setHidden(true);
		
		self::$application->find('help');
	}
	
	/**
	 * @covers ::has()
	 */
	public function testHas()
	{
		$this->assertTrue(self::$application->has('help'));
		$this->assertTrue(self::$application->has('list'));
	}
	
	/**
	 * @covers ::has()
	 * @depends testHas
	 */
	public function testHasInvalidCommand()
	{
		$this->assertFalse(self::$application->has('invalid'));
	}
	
	/**
	 * @covers ::has()
	 * @depends testHasInvalidCommand
	 */
	public function testHasHiddenCommand()
	{
		/** @var Command $command */
		$command = self::$application->find('help');
		$command->setHidden(true);
		
		$this->assertFalse(self::$application->has('help'));
	}
	
	/**
	 * @covers ::all()
	 */
	public function testAll()
	{
		$this->setUp('working', 'app');
		
		$this->assertEquals(array(
			'cache:warmup',
			'debug:config',
			'debug:container',
			'config:dump-reference',
			'lint:xliff',
			'translation:update',
			'about',
			'cache:clear',
			'assets:install',
			'cache:pool:clear',
			'debug:translation',
			'lint:yaml',
			'debug:event-dispatcher',
			'multikernel:convert',
			'list',
			'help',
			'server:log',
			'server:run',
			'server:start',
			'server:stop',
			'server:status',
		), array_keys(self::$application->all()));
	}
	
	/**
	 * @covers ::all()
	 * @depends testAll
	 */
	public function testAllNamespace()
	{
		$this->assertEquals(array(
			'debug:config',
			'debug:container',
			'debug:translation',
			'debug:event-dispatcher',
		), array_keys(self::$application->all('debug')));
	}
	
	/**
	 * @covers ::all()
	 * @depends testAllNamespace
	 */
	public function testAllHiddenCommand()
	{
		$this->setUp('working', 'app');
		
		/** @var Command $command */
		$command = self::$application->find('help');
		$command->setHidden(true);
		
		$this->assertEquals(array(
			'cache:warmup',
			'debug:config',
			'debug:container',
			'config:dump-reference',
			'lint:xliff',
			'translation:update',
			'about',
			'cache:clear',
			'assets:install',
			'cache:pool:clear',
			'debug:translation',
			'lint:yaml',
			'debug:event-dispatcher',
			'multikernel:convert',
			'list',
			'server:log',
			'server:run',
			'server:start',
			'server:stop',
			'server:status',
		), array_keys(self::$application->all()));
	}
}
