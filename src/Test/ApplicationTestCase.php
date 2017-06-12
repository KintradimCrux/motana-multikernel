<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Test;

use Motana\Bundle\MultiKernelBundle\Console\Application;
use Motana\Bundle\MultiKernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultiKernelBundle\Console\MultiKernelApplication;

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
	 * @see \Motana\Bundle\MultiKernelBundle\Test\KernelTestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = null, $environment = 'test', $debug = false)
	{
		parent::setUp($type, $app, $environment, $debug);
		
		if (self::$kernel instanceof BootKernel) {
			self::$application = new MultiKernelApplication(self::$kernel);
		} else {
			self::$application = new Application(self::$kernel);
		}
	}
}
