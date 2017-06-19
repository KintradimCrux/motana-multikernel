<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Test;

use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

/**
 * Base class for tests requiring an Application.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class ApplicationTestCase extends KernelTestCase
{
	/**
	 * @var Application
	 */
	protected static $application;
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Test\KernelTestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
		
		if (self::$kernel instanceof BootKernel) {
			self::$application = new MultikernelApplication(self::$kernel);
		} else {
			self::$application = new Application(self::$kernel);
		}
	}
}
