<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Abstract base class for testing interactive console commands.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class InteractiveCommandTestCase extends CommandTestCase
{
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\CommandTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		// Call parent method
		parent::setUp($app, $environment, $debug);
	}
	
	/**
	 * Data provider for test_interact().
	 *
	 * @return array
	 */
	abstract public function provide_test_interact_data();
	
	/**
	 * @covers ::interact()
	 * @dataProvider provide_test_interact_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $interactiveInput Interactive input
	 * @param array $expectedOptions Expected input options after interaction
	 * @testdox interact() returns correct output for
	 */
	public function test_interact($app, $template, array $interactiveInput = [], array $expectedOptions = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Get requested format
		$format = 'txt';
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Create the interactive input stream
		$input->setStream(self::createStream($interactiveInput));
		$input->setInteractive(true);
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		$this->callMethod(self::$command, 'interact', $input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the interaction output is correct
		$this->assertEquals($this->getTemplate('interact_' . $template, $options, $format, $this->commandName), self::$output->fetch());
		
		// Check the options contain values from the interaction
		$this->assertEquals($expectedOptions, $input->getOptions());
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 * @testdox execute() returns correct output for
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Get requested format
		$format = 'txt';
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		$this->callMethod(self::$command, 'execute', $input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate('execute_' . $template, $options, $format, $this->commandName), self::$output->fetch());
	}
	
	/**
	 * @covers ::run()
	 * @covers ::execute()
	 * @dataProvider provide_test_run_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @param array $interactiveInput Interactive input
	 * @preserveGlobalState disabled
	 * @testdox run() returns correct output for
	 */
	public function test_run($app, $template, array $parameters = [], array $interactiveInput = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Get requested format
		$format = 'txt';
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Create the interactive input stream
		$input->setStream(self::createStream($interactiveInput));
		$input->setInteractive(true);
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		self::$command->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate('run_' . $template, $options, $format, $this->commandName), self::$output->fetch());
	}

	/**
	 * Create a stream from the provided inputs.
	 *
	 * @param array $inputs Inputs to write to the stream
	 * @return resource
	 */
	protected static function createStream(array $inputs)
	{
		// Create a memory stream
		$stream = fopen('php://memory', 'r+', false);
		
		// Write the inputs to the stream and rewind it
		fwrite($stream, implode(PHP_EOL, $inputs));
		rewind($stream);
		
		// Return the stream
		return $stream;
	}
}
