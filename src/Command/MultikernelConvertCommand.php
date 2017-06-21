<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command for converting a Symfony Standard Edition project to a multi-kernel project.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultikernelConvertCommand extends Command
{
	/**
	 * @var string
	 */
	protected static $skeletonPath = __DIR__ . '/../Resources/skeleton/project';
	
	/**
	 * @var Filesystem
	 */
	protected $fs;
	
	/**
	 * @var OutputInterface
	 */
	protected $output;
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		return 'boot' !== $this->getApplication()->getKernel()->getName();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
	{
		$this->setName('multikernel:convert')
		->setDescription('Converts a project to a multikernel project')
		->setHelp(<<<EOH
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
		);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::initialize()
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->fs = new Filesystem();
		$this->output = $output;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 * @throws FileNotFoundException if no composer.json or no app kernels were found in the working directory
	 * @throws AccessDeniedException if permissions allow no write access to the working directory
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dir = getcwd();
		
		$options = array(
			'copy_on_windows' => 'WIN' === strtoupper(substr(PHP_OS, 0, 3)),
			'override' => true,
		);
		
		// Check there is a composer.json in the project directory
		if ( ! is_file($dir . '/composer.json')) {
			throw new FileNotFoundException(sprintf('No composer.json found in directory "%s".', $dir));
		}
		
		// Check the project directory is writable
		if ( ! is_writable($dir)) {
			throw new AccessDeniedException(sprintf('Not enough permissions to write to the "%s" directory.', $dir));
		}
		
		// Find kernels in ./*/*Kernel.php
		$kernels = iterator_to_array(Finder::create()->files()->name('*Kernel.php')->notName('BootKernel.php')->depth(1)->in($dir));

		// Do nothing when no kernels are found
		if (empty($kernels)) {
			throw new FileNotFoundException(sprintf('No app kernels found in directory "%s".', $dir));
		}
		
		// Output the headline
		$output->writeln(sprintf('<info>Converting the project</info>' . PHP_EOL, $dir));
		
		// Copy the "apps" directory from the project skeleton
		$output->writeln('Creating directory apps/ from skeleton');
		$this->fs->mirror(self::$skeletonPath . '/apps', $dir . '/apps', null, $options);
		
		// Create apps/BootKernel.php
		$output->writeln('Writing apps/BootKernel.php');
		$this->fs->dumpFile($dir . '/apps/BootKernel.php', $this->generateBootKernel());
		
		// Update parameters.yml of the boot kernel
		$output->writeln('Writing apps/config/parameters.yml');
		$this->generateParameters($dir . '/apps/config/parameters.yml', $kernels);
		
		// Move all found apps to apps/<kernelname>/
		foreach ($kernels as $file) {
			/** @var SplFileInfo $file */
			$kernelName = basename($file->getPath());
			$kernelFilename = $file->getBasename();
			$targetDir = $dir . '/apps/' . $kernelName;
			
			$output->writeln(sprintf('Copying directory %s/ to apps/%s/', $kernelName, $kernelName));
			
			// Copy the app
			$this->fs->mirror($file->getPath(), $targetDir, null, $options);
			
			// Remove the old autoload.php
			$this->fs->remove($targetDir . '/autoload.php');
			
			// Update use clauses and strip methods getCacheDir(), getLogDir()
			// and registerContainerConfiguration() from the kernel
			$output->writeln(sprintf('Updating apps/%s/%s', $kernelName, $kernelFilename));
			$this->updateKernel($targetDir . '/' . $kernelFilename);
			
			// Update relative paths to the src/ directory in the configuration for the kernel
			$this->updateConfiguration($targetDir . '/config');
		}

		// Copy bin/console from the project skeleton
		$output->writeln('Updating bin/console');
		$this->fs->mirror(self::$skeletonPath . '/bin', $dir . '/bin', null, $options);
		
		// Copy web/app.php and web/app_dev.php from the project skeleton
		$output->writeln('Updating web/app.php');
		$output->writeln('Updating web/app_dev.php');
		$this->fs->mirror(self::$skeletonPath . '/web', $dir . '/web', null, $options);
		
		// Update classmap in composer.json
		$output->writeln('Updating composer.json');
		$this->updateComposerJson($dir . '/composer.json', $kernels);
		
		// Remove the app directories
		foreach ($kernels as $file) {
			/** @var SplFileInfo $file */
			$output->writeln(sprintf('Removing directory %s/', basename($file->getPath())));
			$this->fs->remove($file->getPath());
		}
		
		// Remove the var/cache, var/logs and var/sessions directories
		foreach (array('cache', 'logs', 'sessions') as $subdir) {
			if (is_dir($dir . '/var/' . $subdir)) {
				$output->writeln(sprintf('Removing directory var/%s/', $subdir));
				$this->fs->remove($dir . '/var/' . $subdir);
			}
		}
		
		// Output a hint on what to do next
		$output->writeln(PHP_EOL . '<info>Done copying files. Now you can run <comment>composer dump-autoload</comment> and <comment>composer symfony-scripts</comment> to finish converting your project.</info>');
	}
	
	/**
	 * Returns the content for BootKernel.php.
	 *
	 * @return string
	 */
	protected function generateBootKernel()
	{
		return <<<EOF
<?php
class BootKernel extends Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel
{
	public function getRootDir()
	{
		return \$this->rootDir = __DIR__;
	}
}

EOF;
	}
	
	/**
	 * Generates the parameters.yml for the boot kernel.
	 *
	 * @param string $filename Filename of parameters.yml
	 * @param SplFileInfo[] $kernels Kernels to remove from classmap
	 */
	protected function generateParameters($filename, array $kernels)
	{
		$content = file_get_contents($filename . '.dist');
		$content = ltrim(preg_replace('|\s*#.*\n|', "\n", $content));
		
		// Set the default kernel name automatically when only one app kernel is available
		if (1 === count($kernels)) {
			$file = current($kernels);
			/** @var SplFileInfo $file */
			$content = preg_replace('|(kernel\.default:)\s*~|', '\1 ' . $file->getRelativePath(), $content);
		}
		
		// Change the secret parameter to something more safe
		$content = str_replace('ThisTokenIsNotSoSecretChangeIt', $this->generateRandomSecret(), $content);
		
		// Update the file
		$this->fs->dumpFile($filename, $content);
	}
	
	/**
	 * Generates a good random value for Symfony's 'secret' option.
	 *
	 * @return string The randomly generated secret
	 */
	protected function generateRandomSecret()
	{
		return hash('sha1', function_exists('openssl_random_pseudo_bytes') ? openssl_random_pseudo_bytes(23) : uniqid(mt_rand(), true));
	}
	
	/**
	 * Update the classmap in composer.json.
	 *
	 * @param string $filename Path to composer.json
	 * @param SplFileInfo[] $kernels Kernels to remove from classmap
	 */
	protected function updateComposerJson($filename, array $kernels)
	{
		// Load composer.json
		$json = json_decode(file_get_contents($filename), true);
		
		// Remove the moved app kernels from the classmap
		if ( ! empty($json['autoload']['classmap'])) {
			foreach ($kernels as $file) {
				/** @var SplFileInfo $file */
				if (false !== $i = array_search($file->getRelativePathname(), $json['autoload']['classmap'])) {
					unset($json['autoload']['classmap'][$i]);
				}
			}
		}
		
		// Add the boot kernel to the classmap
		if (empty($json['autoload']['classmap']) || ! in_array('apps/BootKernel.php', $json['autoload']['classmap'])) {
			$json['autoload']['classmap'][] = 'apps/BootKernel.php';
		}
		
		// Reset indexes on the classmap
		$json['autoload']['classmap'] = array_slice($json['autoload']['classmap'], 0);
		
		// Remove parameters.yml of the moved kernels from incenteev-parameters settings
		$parameters = array();
		if ( ! empty($json['extra']['incenteev-parameters'])) {
			foreach ($json['extra']['incenteev-parameters'] as $record) {
				foreach ($kernels as $file) {
					/** @var SplFileInfo $file */
					if ($record['file'] === $file->getRelativePath() . '/config/parameters.yml') {
						continue;
					}
					
					if (0 === strpos($record['file'], $file->getRelativePath() . '/config/')) {
						$parameters[] = array('file' => 'apps/' . $record['file']);
					}
				}
			}
			
			$json['extra']['incenteev-parameters'] = $parameters;
		}
		
		// Add the parameters.yml of the boot kernel to the incenteev-parameters key
		$json['extra']['incenteev-parameters'] = array(array('file' => 'apps/config/parameters.yml'));
		
		// Add the parameters.yml of the moved kernels to the incenteev-parameters key
		foreach ($kernels as $file) {
			$json['extra']['incenteev-parameters'][] = array('file' => 'apps/' . $file->getRelativePath() . '/config/parameters.yml');
		}
		
		// Merge the new file list with the remaining files of the previous one
		$json['extra']['incenteev-parameters'] = array_merge($json['extra']['incenteev-parameters'], $parameters);
		
		// Reset indexes on incenteev parameters
		$json['extra']['incenteev-parameters'] = array_slice($json['extra']['incenteev-parameters'], 0);
		
		// Update composer.json
		$this->fs->dumpFile($filename, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
	
	/**
	 * Updates paths to the src directory in the configuration files of an app.
	 *
	 * @param string $configDir App configuration directory
	 */
	protected function updateConfiguration($configDir)
	{
		$kernelName = basename(dirname($configDir));
		
		// Process all Yaml configuration files of the app
		foreach (Finder::create()->files()->name('*.yml')->in($configDir) as $file) {
			/** @var SplFileInfo $file */
			$this->output->writeln(sprintf('Updating apps/%s/config/%s', $kernelName, $file->getBasename()));
			
			$content = file_get_contents($file->getPathname());
			
			// Correct relative paths to the src directory
			// Update references to routing.yml
			// Update references to routing_dev.yml
			// Update the session directory
			$content = str_replace(array(
				"'../../src",
				'%kernel.project_dir%/' . $kernelName . '/config/routing.yml',
				'%kernel.project_dir%/' . $kernelName . '/config/routing_dev.yml',
				'%kernel.project_dir%/var/sessions/%kernel.environment%',
			), array(
				"'../../../src",
				'%kernel.project_dir%/apps/' . $kernelName . '/config/routing.yml',
				'%kernel.project_dir%/apps/' . $kernelName . '/config/routing_dev.yml',
				'%kernel.project_dir%/var/sessions/%kernel.name%/%kernel.environment%',
			), $content);
			
			// Replace the configuration file
			$this->fs->dumpFile($file->getPathname(), $content);
		}
	}
	
	/**
	 * Updates an app kernel to work with a multikernel app.
	 *
	 * @param string $filename App kernel filename
	 */
	protected function updateKernel($filename)
	{
		// Read the kernel file
		$code = file_get_contents($filename);
		
		// Replace use clauses
		$code = str_replace('use Symfony\Component\HttpKernel\Kernel;', 'use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;', $code);
		$code = str_replace('use Symfony\Component\Config\Loader\LoaderInterface;'.PHP_EOL, '', $code);
		
		// Strip the methods getCacheDir(), getLogDir() and registerContainerConfiguration(),
		// the methods in the Kernel class replacement return correct paths for a multikernel environment
		$code = preg_replace('|\s*public function getCacheDir\(\)\s*\{.*\}\s*|Uuis', '', $code);
		$code = preg_replace('|\s*public function getLogDir\(\)\s*\{.*\}\s*|Uuis', '', $code);
		$code = preg_replace('|\s*public function registerContainerConfiguration\(LoaderInterface \$loader\)\s*\{.*\}\s*|Uuis', '', $code);
		
		// Update the kernel file
		$this->fs->dumpFile($filename, $code);
	}
}
