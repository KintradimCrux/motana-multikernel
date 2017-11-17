<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase;

use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\KernelTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		// Call parent method
		parent::setUp($app, $environment, $debug);
		
		// Kernel is a BootKernel instance
		if (self::$kernel instanceof BootKernel) {
			self::$application = new MultikernelApplication(self::$kernel);
		}
		
		// Kernel is a regular application kernel instance
		else {
			self::$application = new Application(self::$kernel);
		}
	}
	
	/**
	 * Assert that an InputArgument has the specified properties.
	 *
	 * @param InputArgument $argument The argument to inspect
	 * @param string $expectedClass Expected class name
	 * @param string $argumentName Expected argument name
	 * @param integer $argumentMode Expected argument mode
	 * @param string $argumentDescription Expected argument description
	 */
	public static function assertInputArgument(InputArgument $argument, $expectedClass, $argumentName, $argumentMode, $argumentDescription, $argumentDefaultValue = null)
	{
		// Check the argument is an instance of the expected class
		self::assertInstanceOf($expectedClass, $argument);
		
		// Check the argument name is correct
		self::assertEquals($argumentName, $argument->getName());
		
		// Check the argument mode is correct
		self::assertAttributeEquals($argumentMode, 'mode', $argument);
		
		// Check the argument description is correct
		self::assertEquals($argumentDescription, $argument->getDescription());
		
		// Check the default value is correct
		self::assertEquals($argumentDefaultValue, $argument->getDefault());
	}
	
	/**
	 * Assert that an Inputoption has the specified properties.
	 *
	 * @param InputOption $option The option to inspect
	 * @param string $expectedClass Expected class name
	 * @param string $optionName Expected option name
	 * @param string $optionShortcut Expected option shortcut
	 * @param integer $optionMode Expected option mode
	 * @param string $optionDescription Expected option description
	 * @param string $optionDefaultValue Expected option default value
	 */
	public static function assertInputOption(InputOption $option, $expectedClass, $optionName, $optionShortcut, $optionMode, $optionDescription, $optionDefaultValue = null)
	{
		// Check the option is an instance of the expected class
		self::assertInstanceOf($expectedClass, $option);
		
		// Check the option name is correct
		self::assertEquals($optionName, $option->getName());
		
		// Check the option shortcut is correct
		self::assertEquals($optionShortcut, $option->getShortcut());
		
		// Check the option mode is correct
		self::assertAttributeEquals($optionMode, 'mode', $option);
		
		// Check the option description is correct
		self::assertEquals($optionDescription, $option->getDescription());
		
		// Check the default value is correct
		self::assertEquals($optionDefaultValue, $option->getDefault());
	}
}
