<?php

namespace Tests\Motana\Bundle\MultikernelBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;

use Motana\Bundle\MultikernelBundle\Console\Output\BufferedOutput;
use Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase;
use Motana\Bundle\MultikernelBundle\Test\CommandTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @coversDefaultClass Motana\Bundle\MultikernelBundle\Command\MultikernelConvertCommand
 */
class MultikernelConvertCommandTest extends CommandTestCase
{
	/**
	 * Constructor.
	 *
	 * @param string $name Test name
	 * @param array $data Test dataset
	 * @param string $dataName Test dataset name
	 */
	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName, 'multikernel:convert', array('--format' => 'txt'));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Test\ApplicationTestCase::setUp()
	 */
	protected function setUp($type = 'working', $app = 'app', $environment = 'test', $debug = false, $mergeApplicationDefinition = true)
	{
		if (null !== $type) {
			ApplicationTestCase::setUp($type, $app, $environment, $debug);
			
			self::$command = self::$application->get($this->commandName);
			
			if ($mergeApplicationDefinition) {
				self::$command->mergeApplicationDefinition(true);
			}
			
			self::$output = new BufferedOutput();
		}
	}
	
	/**
	 * @covers ::isEnabled()
	 */
	public function testIsEnabledOnAppKernel()
	{
		$this->assertTrue(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::isEnabled()
	 * @expectedException Symfony\Component\Console\Exception\CommandNotFoundException
	 * @expectedExceptionMessage The command "multikernel:convert" does not exist.
	 */
	public function testIsNotEnabledOnBootKernel()
	{
		$this->setUp('working', null);
		
		$this->assertFalse(self::$command->isEnabled());
	}
	
	/**
	 * @covers ::configure()
	 */
	public function testConfigure()
	{
		$this->setUp('working', 'app', 'test', false, false);
		
		// Check the command name has been initialized correctly
		$this->assertEquals('multikernel:convert', self::$command->getName());
		
		// Check the command description has been initialized correctly
		$this->assertEquals('Converts a project to a multikernel project', self::$command->getDescription());
		
		// Check the command help has been initialized correctly
		$this->assertEquals(<<<EOH
The <info>multikernel:convert</info> command changes the filesystem
structure of a Symfony Standard Edition project to a multikernel project.

The command is only available on a regular app kernel and is disabled
after conversion.

To convert your project to a multikernel project, run:

  <info>php %command.full_name%</info>

After converting the project filesystem structure, run:

  <info>composer dump-autoload</info>
  <info>composer symfony-scripts</info>

EOH
		, self::$command->getHelp());
		
		// Check the input definition of the command has no arguments and options
		$this->assertEmpty(self::$command->getDefinition()->getArguments());
		$this->assertEmpty(self::$command->getDefinition()->getOptions());
	}
	
	/**
	 * @covers ::initialize()
	 */
	public function testInitialize()
	{
		$input = new ArrayInput(array());
		
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		// Check the fs property has been initialized properly
		$this->assertInstanceOf(Filesystem::class, $this->readAttribute(self::$command, 'fs'));
		
		// Check the output property has been initialized properly
		$this->assertSame(self::$output, $this->readAttribute(self::$command, 'output'));
	}
	
	/**
	 * @covers ::generateBootKernel()
	 */
	public function testGenerateBootKernel()
	{
		$this->assertEquals(<<<EOF
<?php
class BootKernel extends Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
{
	public function getRootDir()
	{
		return \$this->rootDir = __DIR__;
	}
}

EOF
		, $this->callMethod(self::$command, 'generateBootKernel'));
	}
	
	/**
	 * @covers ::generateRandomSecret()
	 */
	public function testGenerateRandomSecret()
	{
		$secret = $this->callMethod(self::$command, 'generateRandomSecret');
		
		// Check the generated secret is a SHA1 hash
		$this->assertRegExp('|^[0-9a-f]{40}$|', $secret);
	}
	
	/**
	 * @covers ::generateParameters()
	 * @depends testGenerateRandomSecret
	 */
	public function testGenerateParameters()
	{
		$dir = self::$fixturesDir . '/kernels/working/apps';
		$file = self::$fixturesDir . '/commands/multikernel_convert/parameters.yml';

		$kernels = iterator_to_array(Finder::create()->files()->name('*Kernel.php')->notName('BootKernel.php')->depth(1)->in($dir));
		
		$input = new ArrayInput(array());
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		$this->callMethod(self::$command, 'generateParameters', $file, $kernels);
		
		$lines = explode(PHP_EOL, file_get_contents($file));
		
		$this->assertEquals(4, count($lines));
		
		$this->assertEquals('parameters:', $lines[0]);
		$this->assertEquals('    kernel.default: app', $lines[1]);
		$this->assertRegExp('|^    kernel.secret: [0-9a-f]{40}$|', $lines[2]);
		$this->assertEmpty($lines[3]);
		
		self::getFs()->remove($file);
	}
	
	/**
	 * @covers ::updateComposerJson()
	 */
	public function testUpdateComposerJson()
	{
		$dir = self::$fixturesDir . '/kernels/working/apps';
		$file = self::$fixturesDir . '/commands/multikernel_convert/composer.json';
		
		$kernels = iterator_to_array(Finder::create()->files()->name('*Kernel.php')->notName('BootKernel.php')->depth(1)->in($dir));
		
		$input = new ArrayInput(array());
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		self::getFs()->copy($file . '.dist', $file);
		
		$this->callMethod(self::$command, 'updateComposerJson', $file, $kernels);
		
		$json = json_decode(file_get_contents($file), true);
		
		// Check the classmap has been changed correctly
		$this->assertTrue(isset($json['autoload']['classmap']));
		$this->assertEquals(array('apps/BootKernel.php'), $json['autoload']['classmap']);
		
		// Check the incenteev-parameters have been changed correctly
		$this->assertTrue(isset($json['extra']['incenteev-parameters']));
		$this->assertEquals(array(
			array('file' => 'apps/config/parameters.yml'),
			array('file' => 'apps/app/config/parameters.yml'),
		), $json['extra']['incenteev-parameters']);
		
		self::getFs()->remove($file);
	}
	
	/**
	 * @covers ::updateComposerJson()
	 * @depends testUpdateComposerJson
	 */
	public function testUpdateComposerJsonDoesNotRemoveExtraFilesFromIncenteevParameters()
	{
		$dir = self::$fixturesDir . '/kernels/working/apps';
		$file = self::$fixturesDir . '/commands/multikernel_convert/composer.json';
		
		$kernels = iterator_to_array(Finder::create()->files()->name('*Kernel.php')->notName('BootKernel.php')->depth(1)->in($dir));
		
		$input = new ArrayInput(array());
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		self::getFs()->copy(str_replace('.json', '-extra.json.dist', $file), $file);
		
		$this->callMethod(self::$command, 'updateComposerJson', $file, $kernels);
		
		$json = json_decode(file_get_contents($file), true);
		
		// Check the classmap has been changed correctly
		$this->assertTrue(isset($json['autoload']['classmap']));
		$this->assertEquals(array('apps/BootKernel.php'), $json['autoload']['classmap']);
		
		// Check the incenteev-parameters have been changed correctly
		$this->assertTrue(isset($json['extra']['incenteev-parameters']));
		$this->assertEquals(array(
			array('file' => 'apps/config/parameters.yml'),
			array('file' => 'apps/app/config/parameters.yml'),
			array('file' => 'apps/app/config/foobar.yml'),
		), $json['extra']['incenteev-parameters']);
		
		self::getFs()->remove($file);
	}
	
	/**
	 * @covers ::updateConfiguration()
	 */
	public function testUpdateConfiguration()
	{
		$dir = self::$fixturesDir . '/commands/multikernel_convert/app/config';
		
		$files = array();
		foreach (Finder::create()->files()->name('*.yml.dist')->in($dir) as $file) {
			/** @var SplFileInfo $file */
			$files[] = $filename = $file->getPath() . '/' . $file->getBasename('.dist');
			self::getFs()->copy($file->getPathname(), $filename);
		}
		
		$input = new ArrayInput(array());
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		$this->callMethod(self::$command, 'updateConfiguration', $dir);
		
		$this->assertEquals(<<<EOF
Updating apps/app/config/config.yml
Updating apps/app/config/config_dev.yml
Updating apps/app/config/services.yml

EOF
		, self::$output->fetch());
		
		foreach ($files as $file) {
			$content = file_get_contents($file);
			switch (basename($file)) {
				case 'config.yml':
					$this->assertNotContains('%kernel.project_dir%/app/config/routing.yml', $content);
					$this->assertContains('%kernel.project_dir%/apps/app/config/routing.yml', $content);
					$this->assertNotContains('%kernel.project_dir%/var/sessions/%kernel.environment%', $content);
					$this->assertContains('%kernel.project_dir%/var/sessions/%kernel.name%/%kernel.environment%', $content);
					break;
				case 'config_dev.yml':
					$this->assertNotContains('%kernel.project_dir%/app/config/routing_dev.yml', $content);
					$this->assertContains('%kernel.project_dir%/apps/app/config/routing_dev.yml', $content);
					break;
				case 'services.yml':
					$this->assertNotContains("'../../src/", $content);
					$this->assertContains("'../../../src/", $content);
					break;
			}
			
			self::getFs()->remove($file);
		}
	}
	
	/**
	 * @covers ::updateKernel()
	 */
	public function testUpdateKernel()
	{
		$file = self::$fixturesDir . '/commands/multikernel_convert/app/AppKernel.php';
		
		self::getFs()->copy($file . '.dist', $file);

		$input = new ArrayInput(array());
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		$this->callMethod(self::$command, 'updateKernel', $file);
		
		$content = file_get_contents($file);
		
		$this->assertNotContains('use Symfony\Component\HttpKernel\Kernel;', $content);
		$this->assertContains('use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;', $content);
		
		$this->assertNotContains('use Symfony\Component\Config\Loader\LoaderInterface;', $content);
		
		$this->assertNotContains('public function getCacheDir()', $content);
		$this->assertNotContains('public function getLogDir()', $content);
		$this->assertNotContains('public function registerContainerConfiguration(LoaderInterface $loader)', $content);
		
		self::getFs()->remove($file);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Bundle\MultikernelBundle\Test\CommandTestCase::provide_testExecute_data()
	 */
	public function provide_testExecute_data() {
		return array(
			array('working', 'app', 'command_appkernel', array()),
		);
	}
	
	/**
	 * @covers ::execute()
	 * @dataProvider provide_testExecute_data
	 * @param string $type Type (broken | working)
	 * @param string $app App subdirectory name
	 * @param string $template Template name
	 * @param array $parameters Input parameters
	 */
	public function testExecute($type, $app, $template, array $parameters = array())
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp($type, $app);

		$cwd = getcwd();
		
		$dir = self::$fixturesDir . '/commands/multikernel_convert/project';
		
		self::getFs()->mirror($dir . '.dist', $dir);
		self::getFs()->mkdir($dir . '/var/cache');
		self::getFs()->mkdir($dir . '/var/logs');
		self::getFs()->mkdir($dir . '/var/sessions');
		
		chdir($dir);
		
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		$parameters = array_merge(array('command' => $this->commandName), $this->commandParameters, $parameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		$this->callMethod(self::$command, 'execute', $input, self::$output);
		
		$options = $this->convertParametersToOptions($parameters);
		
		// Check the command output is correct
		$this->assertEquals($this->getTemplate($template, $options, $format, $this->commandName), self::$output->fetch());
		
		// Check the app directory has been removed
		$this->assertFalse(is_dir($dir . '/app'));
		
		// Check the autoload.php and BootKernel class are in place
		$this->assertTrue(is_dir($dir . '/apps'));
		$this->assertTrue(is_file($dir . '/apps/autoload.php'));
		$this->assertTrue(is_file($dir . '/apps/BootKernel.php'));
		
		// Check the AppCache and AppKernel classes are in place
		$this->assertTrue(is_dir($dir . '/apps/app'));
		$this->assertTrue(is_file($dir . '/apps/app/AppCache.php'));
		$this->assertTrue(is_file($dir . '/apps/app/AppKernel.php'));
		
		// Remove the files generated for the test
		chdir($cwd);
		self::getFs()->remove($dir);
	}
	
	/**
	 * @covers ::execute()
	 * @depends testExecute
	 * @expectedException Symfony\Component\Filesystem\Exception\FileNotFoundException
	 * @expectedExceptionMessageRegExp |^No composer\.json found in directory "(.*)"\.$|
	 */
	public function testExecuteChecksComposerJsonExists()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working', 'app');
		
		$cwd = getcwd();
		
		$dir = self::$fixturesDir . '/commands/multikernel_convert/project';
		
		self::getFs()->mkdir($dir);
		
		chdir($dir);
		
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		$parameters = array_merge(array('command' => $this->commandName), $this->commandParameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		try {
			$this->callMethod(self::$command, 'execute', $input, self::$output);
		}
		finally {
			chdir($cwd);
			self::getFs()->remove($dir);
		}
	}
	
	/**
	 * @covers ::execute()
	 * @depends testExecuteChecksComposerJsonExists
	 * @expectedException Symfony\Component\Finder\Exception\AccessDeniedException
	 * @expectedExceptionMessageRegExp |^Not enough permissions to write to the "(.*)" directory\.$|
	 */
	public function testExecuteChecksPermissions()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working', 'app');
		
		$cwd = getcwd();
		
		$dir = self::$fixturesDir . '/commands/multikernel_convert/project';
		
		self::getFs()->mkdir($dir);
		self::getFs()->copy($dir . '.dist/composer.json', $dir . '/composer.json');
		self::getFs()->chmod($dir, 0555);
		
		chdir($dir);
		
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		$parameters = array_merge(array('command' => $this->commandName), $this->commandParameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		try {
			$this->callMethod(self::$command, 'execute', $input, self::$output);
		}
		finally {
			chdir($cwd);
			self::getFs()->chmod($dir, 0777);
			self::getFs()->remove($dir);
		}
	}
	
	/**
	 * @covers ::execute()
	 * @depends testExecuteChecksPermissions
	 * @expectedException Symfony\Component\Filesystem\Exception\FileNotFoundException
	 * @expectedExceptionMessageRegExp |^No app kernels found in directory "(.*)"\.$|
	 */
	public function testExecuteChecksKernels()
	{
		$_SERVER['PHP_SELF'] = 'bin/console';
		
		$this->setUp('working', 'app');
		
		$cwd = getcwd();
		
		$dir = self::$fixturesDir . '/commands/multikernel_convert/project';
		
		self::getFs()->mkdir($dir);
		self::getFs()->copy($dir . '.dist/composer.json', $dir . '/composer.json');
		
		chdir($dir);
		
		$format = isset($parameters['--format']) ? $parameters['--format'] : 'txt';
		
		$parameters = array_merge(array('command' => $this->commandName), $this->commandParameters);
		
		$input = new ArrayInput($this->filterCommandParameters($parameters));
		$input->bind(self::$command->getDefinition());
		
		$this->callMethod(self::$command, 'initialize', $input, self::$output);
		
		try {
			$this->callMethod(self::$command, 'execute', $input, self::$output);
		}
		finally {
			chdir($cwd);
			self::getFs()->remove($dir);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Test\CommandTestCase::convertParametersToOptions()
	 */
	protected static function convertParametersToOptions(array $parameters = array())
	{
		return array();
	}
	
	/**
	 * {@inheritDoc}
	 * @see Motana\Bundle\MultikernelBundle\Test\CommandTestCase::filterCommandParameters()
	 */
	protected static function filterCommandParameters(array $parameters = array())
	{
		unset($parameters['--format']);
		
		return $parameters;
	}
}
