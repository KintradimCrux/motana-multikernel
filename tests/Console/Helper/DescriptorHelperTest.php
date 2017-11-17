<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\Console\Helper;

use Motana\Bundle\MultikernelBundle\Console\Descriptor\JsonDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\MarkdownDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\TextDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\XmlDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Helper\DescriptorHelper;
use Motana\Bundle\MultikernelBundle\Generator\FixtureGenerator;
use Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Console\Helper\DescriptorHelper
 * @testdox Motana\Bundle\MultikernelBundle\Console\Helper\DescriptorHelper
 */
class DescriptorHelperTest extends ApplicationTestCase
{
	/**
	 * The project directory.
	 *
	 * @var string
	 */
	protected static $projectDir;
	
	/**
	 * The helper to test.
	 *
	 * @var DescriptorHelper
	 */
	protected static $helper;
	
	/**
	 * Output of the helper.
	 *
	 * @var BufferedOutput
	 */
	protected static $output;
	
	/**
	 * A Command used for the tests.
	 *
	 * @var Command
	 */
	protected static $command;
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase\ApplicationTestCase::setUp()
	 */
	protected function setUp($fake = true, $app = null, $environment = 'test', $debug = false)
	{
		if (null === self::$projectDir) {
			$dir = __DIR__;
			while ( ! is_file($dir . '/composer.json')) {
				$dir = dirname($dir);
			}
			self::$projectDir = $dir;
		}
		
		self::$helper = new DescriptorHelper();
		
		if (false === $fake) {
			parent::setUp($app, $environment, $debug);
			self::$application->getKernel()->boot();
			self::$helper->setContainer(self::$application->getKernel()->getContainer());
		} else {
			self::$application = null;
			$container = new ContainerBuilder();
			$container->setParameter('kernel.project_dir', self::$projectDir);
			self::$helper->setContainer($container);
		}
		
		self::$output = new BufferedOutput();
		
		if (false === $fake) {
			self::$command = self::$application->find('help');
		} else {
			self::$command = new Command('test');
			self::$command->setDescription('DescriptorHelper test command');
			self::$command->setHelp(<<<EOH
Tests the DescriptorHelper.
					
%command.name%
php %command.full_name%
EOH
					);
			self::$command->setDefinition([
				new InputArgument('method', InputArgument::OPTIONAL, $description = 'Test method'),
				new InputOption('--all', '-a', InputOption::VALUE_NONE, 'Run all tests'),
				new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'File to process'),
				new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			]);
		}
	}
	
	/**
	 * @covers ::__construct()
	 * @covers ::register()
	 * @testdox __construct() sets up properties correctly
	 */
	public function test_constructor()
	{
		$descriptors = $this->readAttribute(self::$helper, 'descriptors');
		
		// Check the number of registered descriptors is correct
		$this->assertEquals(4, count($descriptors));
		
		// Check the registered formats are correct
		$this->assertEquals([ 'txt', 'xml', 'json', 'md' ], array_keys($descriptors));
		
		// Check the registered descriptors are instances of the correct classes
		$this->assertInstanceOf(TextDescriptor::class, $descriptors['txt']);
		$this->assertInstanceOf(XmlDescriptor::class, $descriptors['xml']);
		$this->assertInstanceOf(JsonDescriptor::class, $descriptors['json']);
		$this->assertInstanceOf(MarkdownDescriptor::class, $descriptors['md']);
	}
	
	/**
	 * @covers ::setContainer()
	 * @testdox setContainer() sets container property of helper and descriptors
	 */
	public function test_setContainer()
	{
		$container = new ContainerBuilder();
		self::$helper->setContainer($container);
		
		// Check the container property of the helper is set
		$this->assertSame($container, $this->readAttribute(self::$helper, 'container'));
		
		// Check the container property of the descriptors is set
		$descriptors = $this->readAttribute(self::$helper, 'descriptors');
		foreach ($descriptors as $descriptor) {
			$this->assertSame($container, $this->readAttribute($descriptor, 'container'));
		}
	}
	
	/**
	 * @covers ::getName()
	 * @testdox getName() returns helper name
	 */
	public function test_getName()
	{
		// Check the returned name is correct
		$this->assertEquals('descriptor', self::$helper->getName());
	}
	
	/**
	 * Object provider for testDescribe().
	 *
	 * @param string $template Template name
	 * @return mixed
	 */
	public function provide_test_describe_object($template)
	{
		switch ($template) {
			case 'argument':
				return self::$command->getDefinition()->getArgument(0);
			case 'argument_with_default':
				$object = self::$command->getDefinition()->getArgument(0);
				$object->setDefault('standard');
				return $object;
			case 'option':
				return self::$command->getDefinition()->getOption('all');
				break;
			case 'option_with_default':
				$object = self::$command->getDefinition()->getOption('file');
				$object->setDefault('autoexec.bat');
				return $object;
			case 'definition':
				return self::$command->getDefinition();
			case 'command_multikernel':
			case 'command_appkernel':
				return self::$command;
			case 'application_multikernel':
			case 'application_appkernel':
				return self::$application;
		}
	}
	
	/**
	 * Data provider for test_describe().
	 *
	 * @return array
	 */
	public function provide_test_describe_data()
	{
		return [
			'an InputArgument (json format)' => [
				true,
				null,
				'argument',
				'json',
				[]
			],
			'an InputArgument (md format)' => [
				true,
				null,
				'argument',
				'md',
				[]
			],
			'an InputArgument (text format)' => [
				true,
				null,
				'argument',
				'txt',
				[]
			],
			'an InputArgument (xml format)' => [
				true,
				null,
				'argument',
				'xml',
				[]
			],
			'an InputArgument with default value (json format)' => [
				true,
				null,
				'argument_with_default',
				'json',
				[]
			],
			'an InputArgument with default value (md format)' => [
				true,
				null,
				'argument_with_default',
				'md',
				[]
			],
			'an InputArgument with default value (text format)' => [
				true,
				null,
				'argument_with_default',
				'txt',
				[]
			],
			'an InputArgument with default value (xml format)' => [
				true,
				null,
				'argument_with_default',
				'xml',
				[]
			],
			'an InputOption (json format)' => [
				true,
				null,
				'option',
				'json',
				[]
			],
			'an InputOption (md format)' => [
				true,
				null,
				'option',
				'md',
				[]
			],
			'an InputOption (text format)' => [
				true,
				null,
				'option',
				'txt',
				[]
			],
			'an InputOption (xml format)' => [
				true,
				null,
				'option',
				'xml',
				[]
			],
			'an InputOption with default value (json format)' => [
				true,
				null,
				'option_with_default',
				'json',
				[]
			],
			'an InputOption with default value (md format)' => [
				true,
				null,
				'option_with_default',
				'md',
				[]
			],
			'an InputOption with default value (text format)' => [
				true,
				null,
				'option_with_default',
				'txt',
				[]
			],
			'an InputOption with default value (xml format)' => [
				true,
				null,
				'option_with_default',
				'xml',
				[]
			],
			'an InputDefinition (json format)' => [
				true,
				null,
				'definition',
				'json',
				[]
			],
			'an InputDefinition (md format)' => [
				true,
				null,
				'definition',
				'md',
				[]
			],
			'an InputDefinition (text format)' => [
				true,
				null,
				'definition',
				'txt',
				[]
			],
			'an InputDefinition (xml format)' => [
				true,
				null,
				'definition',
				'xml',
				[]
			],
			'a MultikernelApplication command (json format)' => [
				false,
				null,
				'command_multikernel',
				'json',
				[]
			],
			'a MultikernelApplication command (md format)' => [
				false,
				null,
				'command_multikernel',
				'md',
				[]
			],
			'a MultikernelApplication command (text format)' => [
				false,
				null,
				'command_multikernel',
				'txt',
				[]
			],
			'a MultikernelApplication command (xml format)' => [
				false,
				null,
				'command_multikernel',
				'xml',
				[]
			],
			'an Application command (json format)' => [
				false,
				'app',
				'command_appkernel',
				'json',
				[]
			],
			'an Application command (md format)' => [
				false,
				'app',
				'command_appkernel',
				'md',
				[]
			],
			'an Application command (text format)' => [
				false,
				'app',
				'command_appkernel',
				'txt',
				[]
			],
			'an Application command (xml format)' => [
				false,
				'app',
				'command_appkernel',
				'xml',
				[]
			],
			'a MultikernelApplication (json format)' => [
				false,
				null,
				'application_multikernel',
				'json',
				[]
			],
			'a MultikernelApplication (json format, namespace debug)' => [
				false,
				null,
				'application_multikernel',
				'json',
				[
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication (md format)' => [
				false,
				null,
				'application_multikernel',
				'md',
				[]
			],
			'a MultikernelApplication (md format, namespace debug)' => [
				false,
				null,
				'application_multikernel',
				'md',
				[
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication (text format)' => [
				false,
				null,
				'application_multikernel',
				'txt',
				[]
			],
			'a MultikernelApplication (text format, namespace debug)' => [
				false,
				null,
				'application_multikernel',
				'txt',
				[
					'namespace' => 'debug'
				]
			],
			'a MultikernelApplication (raw text format)' => [
				false,
				null,
				'application_multikernel',
				'txt',
				[
					'raw_text' => true
				]
			],
			'a MultikernelApplication (raw text format, namespace debug)' => [
				false,
				null,
				'application_multikernel',
				'txt',
				[
					'namespace' => 'debug',
					'raw_text' => true
				]
			],
			'a MultikernelApplication (xml format)' => [
				false,
				null,
				'application_multikernel',
				'xml',
				[]
			],
			'a MultikernelApplication (xml format, namespace debug)' => [
				false,
				null,
				'application_multikernel',
				'xml',
				[
					'namespace' => 'debug'
				]
			],
			'an Application (json format)' => [
				false,
				'app',
				'application_appkernel',
				'json',
				[]
			],
			'an Application (json format, namespace debug)' => [
				false,
				'app',
				'application_appkernel',
				'json',
				[
					'namespace' => 'debug'
				]
			],
			'an Application (md format)' => [
				false,
				'app',
				'application_appkernel',
				'md',
				[]
			],
			'an Application (md format, namespace debug)' => [
				false,
				'app',
				'application_appkernel',
				'md',
				[
					'namespace' => 'debug'
				]
			],
			'an Application (text format)' => [
				false,
				'app',
				'application_appkernel',
				'txt',
				[]
			],
			'an Application (text format, namespace debug)' => [
				false,
				'app',
				'application_appkernel',
				'txt',
				[
					'namespace' => 'debug'
				]
			],
			'an Application (raw text format)' => [
				false,
				'app',
				'application_appkernel',
				'txt',
				[
					'raw_text' => true
				]
			],
			'an Application (raw text format, namespace debug)' => [
				false,
				'app',
				'application_appkernel',
				'txt',
				[
					'namespace' => 'debug',
					'raw_text' => true
				]
			],
			'an Application (xml format)' => [
				false,
				'app',
				'application_appkernel',
				'xml',
				[]
			],
			'an Application (xml format, namespace debug)' => [
				false,
				'app',
				'application_appkernel',
				'xml',
				[
					'namespace' => 'debug'
				]
			],
		];
	}
	
	/**
	 * @covers ::describe()
	 * @dataProvider provide_test_describe_data
	 * @param string $fake Boolean indicating to fake the environment
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param string $format Output format
	 * @param array $options Display options
	 * @testdox describe() returns correct output for
	 */
	public function test_describe($fake, $app, $template, $format, array $options = [])
	{
		// Fake a different script name for the test
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		// Set up an environment of the requested type
		$this->setUp($fake, $app);
		
		// Get the object to describe
		$object = $this->provide_test_describe_object($template);
		
		// Describe the object
		self::$helper->describe(self::$output, $object, array_merge($options, [ 'format' => $format ]));
		
		// Check the descriptor output is correct
		$this->assertEquals($this->getTemplate($template, $options, $format), self::$output->fetch());
	}
	
	/**
	 * @covers ::describe()
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Unsupported format "invalid".
	 * @testdox describe() throws InvalidArgumentException for unsupported formats
	 */
	public function test_describe_with_unsupported_format()
	{
		// Check an exception is thrown when an invalid format is specified
		self::$helper->describe(self::$output, null, [ 'format' => 'invalid' ]);
	}
	
	/**
	 * Returns the expected output for each of the tests.
	 *
	 * @param string $case Template base name
	 * @param array $options Display options
	 * @param string $format Output format
	 * @param boolean $generateTemplates Boolean indicating to generate templates
	 * @return string
	 */
	protected static function getTemplate($case, array $options = [], $format, $generateTemplates = false)
	{
		// Append options to template basename
		$case .= ! empty($options) ? '_' . implode('_', array_keys($options)) : '';
		
		// Insert twig variables into output and save it to a sample file when requested
		if ($generateTemplates) {
			$output = clone(self::$output);
			$content = $output->fetch();
			if ( ! empty($content)) {
				$content = str_replace([
					\Symfony\Component\HttpKernel\Kernel::VERSION,
					'console boot',
					'console app',
					'kernel="boot"',
					'kernel="app"',
				], [
					'{{ kernel_version }}',
					'console {{ kernel_name }}',
					'console {{ kernel_name }}',
					'kernel="{{ kernel_name }}"',
					'kernel="{{ kernel_name }}"',
				], $content);
				self::getFs()->dumpFile(__DIR__ . '/../../../src/Resources/fixtures/descriptor/' . $format . '/' . $case . '.' . $format . '.twig', $content);
			}
		}
		
		// Generate expected content from a template
		$generator = new FixtureGenerator();
		return $generator->generateDescriptorOutput($case, $format, [
			'kernel_name' => false !== strpos($case, 'multikernel') ? 'boot' : 'app',
		]);
	}
}
