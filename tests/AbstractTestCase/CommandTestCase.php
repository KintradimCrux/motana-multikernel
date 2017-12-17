<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase;

use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Abstract base class for testing console commands.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class CommandTestCase extends ApplicationTestCase
{
	/**
	 * Name of the command to test.
	 *
	 * @var string
	 */
	protected $commandName;
	
	/**
	 * Command parameters.
	 *
	 * @var array
	 */
	protected $commandParameters = [];
	
	/**
	 * Command instance.
	 *
	 * @var Command
	 */
	protected static $command;
	
	/**
	 * Output buffer.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * Constructor.
	 *
	 * @param string $name Test name
	 * @param array $data Test dataset
	 * @param string $dataName Test dataset name
	 * @param string $commandName Name of the command to test
	 * @param array $parameters Command parameters
	 */
	public function __construct($name = null, array $data = [], $dataName = '', $commandName = null, array $parameters=[])
	{
		parent::__construct($name, $data, $dataName);
		
		$this->commandName = $commandName;
		$this->commandParameters = $parameters;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase::setUp()
	 */
	protected function setUp($app = null, $environment = 'test', $debug = false)
	{
		// Call parent method
		parent::setUp($app, $environment, $debug);

		// Get the command instance to test
		self::$command = self::$application->get($this->commandName);
		self::$command->mergeApplicationDefinition(true);
		
		// Create a buffered output for the tests
		self::$output = new BufferedOutput();
	}
	
	/**
	 * Data provider for test_execute().
	 *
	 * @return array
	 */
	abstract public function provide_test_execute_data();
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_test_execute_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox execute() returns correct output for
	 */
	public function test_execute($app, $template, array $parameters = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Merge command parameters
		$parameters = array_merge([ 'command' => $this->commandName ], $this->commandParameters, $parameters);
		
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
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
	}
	
	/**
	 * Data provider for test_run().
	 *
	 * @return array
	 */
	abstract public function provide_test_run_data();
	
	/**
	 * @covers ::run()
	 * @covers ::execute()
	 * @dataProvider provide_test_run_data
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 * @testdox run() returns correct output for
	 */
	public function test_run($app, $template, array $parameters = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up the kernel environment
		$this->setUp($app);
		
		// Get requested format
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		// Merge command parameters
		$parameters = array_merge([
			'command' => $this->commandName
		], $this->commandParameters, $parameters);
		
		// Set kernel parameter if no app is specified
		if (null === $app) {
			$parameters['kernel'] = $app;
		}
		
		// Create an input for the command
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		
		// Bind input to command definition
		$input->bind(self::$command->getDefinition());
		
		// Set the container on container aware commands
		if (self::$command instanceof ContainerAwareInterface) {
			self::$command->setContainer(self::$application->getKernel()->getContainer());
		}
		
		// Invoke the command
		self::$command->run($input, self::$output);
		
		// Convert parameters to display options
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
	}
	
	/**
	 * Convert input parameters to display options for the template.
	 *
	 * @param array $parameters Parameters to convert
	 * @return array
	 */
	protected static function convertParametersToOptions(array $parameters = [])
	{
		// Return the input
		return $parameters;
	}
	
	/**
	 * Filters input command parameters before binding input.
	 *
	 * @param array $parameters Parameters to filter
	 * @return array
	 */
	protected static function filterCommandParameters(array $parameters = [])
	{
		// Return the input
		return $parameters;
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format
	 * @param string $commandName Command name
	 * @param boolean $generateTemplates Boolean indicating to generate templates
	 * @return string
	 */
	protected static function getTemplate($case, array $options = [], $format, $commandName, $generateTemplates = false)
	{
		// Replace colons by underscores in the command name
		$commandName = str_replace(':', '_', $commandName);
		
		// Append options to template basename
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';

		// Get the project directory
		$projectDir = realpath(__DIR__ . '/../..');
		
		// Insert twig variables into output and save it to a sample file when requested
		if ($generateTemplates) {
			$output = clone(self::$output);
			$content = $output->fetch();
			if ( ! empty($content)) {
				$content = str_replace([
					\Symfony\Component\HttpKernel\Kernel::VERSION,
					'console boot',
					'console app',
					'(kernel: boot,',
					'(kernel: app,',
					$projectDir,
				], [
					'{{ kernel_version }}',
					'console {{ kernel_name }}',
					'console {{ kernel_name }}',
					'(kernel: {{ kernel_name }},',
					'(kernel: {{ kernel_name }},',
					'{{ project_dir }}',
				], $content);
				$content = preg_replace([
					'#/tmp/motana_multikernel_tests_[^/]+/#',
				], [
					'{{ fixture_dir }}/',
				], $content);
				self::getFs()->dumpFile(__DIR__ . '/../../src/Resources/fixtures/commands/' . $commandName . '/' . $format . '/' . $case . '.' . $format . '.twig', $content);
			}
		}
		
		// Generate expected content from a template
		$generator = new FixtureGenerator();
		return $generator->generateCommandOutput($case, $format, [
			'kernel_name' => false !== strpos($case, 'multikernel') ? 'boot' : 'app',
			'command_name' => $commandName,
			'project_dir' => $projectDir,
		]);
	}
}
