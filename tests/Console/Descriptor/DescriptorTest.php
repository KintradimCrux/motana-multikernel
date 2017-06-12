<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\Console\Descriptor;

use Motana\Bundle\MultiKernelBundle\Console\Descriptor\Descriptor;
use Motana\Bundle\MultiKernelBundle\Test\TestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\Console\Descriptor\Descriptor
 */
class DescriptorTest extends TestCase
{
	/**
	 * @var Descriptor
	 */
	protected static $descriptor;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		self::$descriptor = $this->createMock(Descriptor::class);
	}
	
	/**
	 * @covers ::getProcessedHelp()
	 */
	public function testGetProcessedHelp()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$command = new Command('test');
		
		$command->setHelp(<<<EOH
%command.name%
php %command.full_name%
EOH
		);
		
		$expected = <<<EOH
test
bin/console test
EOH;
		$this->assertEquals($expected, $this->callMethod(self::$descriptor, 'getProcessedHelp', $command));
	}
}
