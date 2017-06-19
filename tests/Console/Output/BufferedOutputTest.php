<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultikernelBundle\Console\Output;

use Symfony\Component\HttpKernel\Kernel;

use Motana\Bundle\MultikernelBundle\Console\Output\BufferedOutput;
use Motana\Bundle\MultikernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Output\BufferedOutput
 */
class BufferedOutputTest extends TestCase
{
	/**
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$output = new BufferedOutput();
	}
	
	/**
	 * @covers ::fetch()
	 */
	public function testFetch()
	{
		$this->writeAttribute(self::$output, 'buffer', 'this is a content');
		
		$this->assertEquals('this is a content', self::$output->fetch());
		$this->assertEquals('', self::$output->fetch());
	}
	
	/**
	 * @covers ::doWrite()
	 * @depends testFetch
	 */
	public function testDoWrite()
	{
		self::$output->write('Symfony '.Kernel::VERSION);
		
		$this->assertEquals('Symfony [symfony-version]', self::$output->fetch());
	}
	
	/**
	 * @covers ::doWrite()
	 * @depends testDoWrite
	 */
	public function testDoWriteWithNewline()
	{
		self::$output->write('Symfony '.Kernel::VERSION, true);
		
		$this->assertEquals('Symfony [symfony-version]' . PHP_EOL, self::$output->fetch());
	}
}
