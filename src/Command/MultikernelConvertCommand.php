<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Motana\Bundle\MultikernelBundle\Generator\BootKernelGenerator;
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;
use Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator;
use Motana\Bundle\MultikernelBundle\Manipulator\ConfigurationManipulator;
use Motana\Bundle\MultikernelBundle\Manipulator\FilesystemManipulator;
use Motana\Bundle\MultikernelBundle\Manipulator\KernelManipulator;

use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Command for converting a Symfony Standard Edition project to a multi-kernel project.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MultikernelConvertCommand extends GeneratorCommand
{
	/**
	 * Default command name.
	 *
	 * @var string
	 */
	protected static $defaultName = 'multikernel:convert';
	
	/**
	 * Output class instance.
	 *
	 * @var OutputInterface
	 */
	private $output;
	
	/**
	 * List of app kernel class includefiles found in the project directory.
	 *
	 * @var SplFileInfo[]
	 */
	private $kernelClassFiles;
	
	/**
	 * List of app cache class includefiles found in the project directory.
	 *
	 * @var SplFileInfo[]
	 */
	private $cacheClassFiles;
	
	/**
	 * Array of messages for the generator summary.
	 *
	 * @var boolean
	 */
	private $messages;
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		return in_array($this->getApplication()->getKernel()->getEnvironment(), [ 'dev', 'test' ]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
	{
		$this
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
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 * @throws FileNotFoundException if no composer.json or no app kernels were found in the working directory
	 * @throws AccessDeniedException if permissions allow no write access to the working directory
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Set the output property
		$this->output = $output;
		
		// Target directory is the current working directory
		$dir = getcwd();
		
		// Output the headline section
		$questionHelper = $this->getQuestionHelper();
		$questionHelper->writeSection($output, sprintf('Converting the project in %s', $dir));
		
		// Check the directory contains a composer.json and is writable
		try {
			$this->checkProjectDirectory($dir);
		}
		
		// Catch any exceptions thrown during the check
		catch (\InvalidArgumentException $e)
		{
			// Print the error message
			$questionHelper->writeGeneratorSummary($output, [
				$e->getMessage(),
				'',
			]);
			
			// Return with the exception code as exitcode
			return $e->getCode();
		}
		
		// Generate the boot kernel skeleton
		$this->createBootKernelSkeleton($dir);
		
		// Copy the kernel directory of each app
		foreach ($this->kernelClassFiles as $file) {
			/** @var SplFileInfo $file */
			$this->copyKernel($dir, $file);
		}
		
		// Update composer.json
		$this->updateComposerJson($dir);
		
		// Remove the old kernel directories and all cache, log and session files
		$this->cleanupProjectDirectory($dir);
		
		// Output the generator summary
		$questionHelper->writeGeneratorSummary($output, $this->messages);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand::createGenerator()
	 */
	protected function createGenerator()
	{
		return new BootKernelGenerator();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand::getSkeletonDirs()
	 */
	protected function getSkeletonDirs(BundleInterface $bundle = null)
	{
		// Get the SensioGeneratorBundle skeleton dirs
		$skeletonDirs = parent::getSkeletonDirs($bundle);
		
		// Add the skeleton directory for the specified bundle, if it exists
		if (isset($bundle) && is_dir($dir = $bundle->getPath() . '/Resources/MotanaMultikernelBundle/skeleton')) {
			$skeletonDirs[] = $dir;
		}
		
		// Add the skeleton directory for the app, if it exists
		if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir() . '/Resources/MotanaMultikernelBundle/skeleton')) {
			$skeletonDirs[] = $dir;
		}
		
		// Add the skeleton directories of the multikernel bundle
		$skeletonDirs[] = __DIR__ . '/../Resources/skeleton';
		$skeletonDirs[] = __DIR__ . '/../Resources';
		
		// Return the skeleton directories
		return $skeletonDirs;
	}
	
	/**
	 * Check the project directory contains a composer.json and is writable.
	 *
	 * @param string $dir Project directory
	 * @throws \InvalidArgumentException
	 */
	private function checkProjectDirectory($dir)
	{
		// Check the directory contains a composer.json
		if ( ! is_file($dir . '/composer.json')) {
			throw new \InvalidArgumentException(sprintf('No composer.json found in the "%s" directory.', $dir), 1);
		}
		
		// Check the directory is writable
		if ( ! is_writable($dir)) {
			throw new \InvalidArgumentException(sprintf('Not enough permissions to write to the "%s" directory.', $dir), 2);
		}
		
		// Search for app kernel class includefiles in the directory
		$this->kernelClassFiles = iterator_to_array(
			Finder::create()
			->files()
			->name('*Kernel.php')
			->notName('BootKernel.php')
			->depth(1)
			->in($dir)
		);
		
		// Check there a kernel class includefiles in the directory
		if (empty($this->kernelClassFiles)) {
			throw new \InvalidArgumentException(sprintf('No app kernels found in the "%s" directory.', $dir), 3);
		}
		
		// Search for app cache class includefiles in the directory
		$this->cacheClassFiles = iterator_to_array(
			Finder::create()
			->files()
			->name('*Cache.php')
			->depth(1)
			->in($dir)
		);
	}

	/**
	 * Create a boot kernel skeleton into the specified directory.
	 *
	 * @param string $dir Target directory
	 * @return void
	 */
	private function createBootKernelSkeleton($dir)
	{
		// Output the headline
		$this->output->writeln(sprintf('> Creating a boot kernel skeleton into <info>%s</info>', $dir));
		
		// Get the generator
		$bundle = $this->getApplication()->getKernel()->getBundle('MotanaMultikernelBundle');
		$generator = $this->getGenerator($bundle);
		/** @var BootKernelGenerator $generator */
		
		// Generate the boot kernel skeleton
		$generator->generateBootkernel(array_map(function($file) {
			return basename($file->getPath());
		}, $this->kernelClassFiles), $dir);
		
		// Output a blank line
		$this->output->write("\n");
	}
	
	/**
	 * Copy the directory of a kernel to its target destination.
	 *
	 * @param string $dir Project directory
	 * @param SplFileInfo $file Kernel class file object
	 * @return void
	 */
	private function copyKernel($dir, SplFileInfo $file)
	{
		// Get the basename of the kernel directory and file
		$kernelDir = basename($file->getPath());
		$kernelFilename = $file->getBasename();
		
		// Get the kernel class name and generate a temporary class name from it
		$kernelClass = $file->getBasename('.php');
		$tempKernelClass = substr($kernelClass, 0, -1) . '_';
		
		// Output the headline
		$this->output->writeln(sprintf('> Copying app <info>%s</info>', $kernelDir));
		
		// Get the target directory
		$targetDir = $dir . '/apps/' . $kernelDir;
		
		// Copy the app and remove its autoload.php
		$this->copyKernelDirectory($file->getPath(), $targetDir);

		// Update the kernel class
		$this->updateKernelClass($targetDir . '/' . $kernelFilename);
		
		// Update the configuration of the kernel
		$this->updateKernelConfiguration($targetDir . '/config');
		
		// Output a blank line
		$this->output->write("\n");
	}

	/**
	 * Copy the app directory of a kernel to its destination.
	 *
	 * @param string $src Source directory
	 * @param string $target Target directory
	 * @return void
	 */
	private function copyKernelDirectory($src, $target)
	{
		// Get the manipulator for the action
		$filesystem = new FilesystemManipulator();
		
		// Copy the app and remove its autoload.php
		$filesystem->mirror($src, $target, null, [
			'copy_on_windows' => 'WIN' === strtoupper(substr(PHP_OS, 0, 3)),
			'override' => true,
		]);
		
		// Remove the autoload.php of the app
		if (is_file($file = $target . '/autoload.php')) {
			$filesystem->remove($file);
		}
	}
	
	/**
	 * Update the code of a kernel for the multikernel environment.
	 *
	 * @param string $file Kernel class file object
	 * @return void
	 */
	private function updateKernelClass($file)
	{
		// Get the running kernel
		$runningKernel = $this->getApplication()->getKernel();
		/** @var Kernel $runningKernel */
		
		// Get the manipulator for the filesystem
		$filesystem = new FilesystemManipulator();
		
		// Determine the current and temporary class name of the kernel
		$kernelClass = basename($file, '.php');
		$tempKernelClass = substr($kernelClass, 0, -1) . '_';
		
		// Rename the kernel class to its temporary name
		$code = file_get_contents($file);
		$code = str_replace($kernelClass, $tempKernelClass, $code);
		$filesystem->dumpFile($file, $code);
		
		// Load and instantiate the kernel
		includeFile($file);
		$kernel = new $tempKernelClass($runningKernel->getEnvironment(), $runningKernel->isDebug());
		
		// Get the manipulator for the kernel source
		$manip = new KernelManipulator($kernel);
		
		// Remove methods provided by the replacement base class
		$manip->removeMethods([
			'getCacheDir',
			'getLogDir',
			'registerContainerConfiguration',
		]);
		
		// Update use clauses in the file
		$manip->replaceUses([
			'Symfony\\Component\\HttpKernel\\Kernel' => 'Motana\\Bundle\\MultikernelBundle\\HttpKernel\\Kernel',
			'Symfony\\Component\\Config\\Loader\\LoaderInterface' => null,
		]);
		
		// Save the file
		$manip->save();
		
		// Rename the kernel class back to its original name
		$code = file_get_contents($file);
		$code = str_replace($tempKernelClass, $kernelClass, $code);
		$filesystem->dumpFile($file, $code);
	}
	
	/**
	 * Updates configuration of a kernel for the multikernel environment.
	 *
	 * @param string $dir App configuration directory
	 * @return void
	 */
	private function updateKernelConfiguration($dir)
	{
		// Find yaml configuration files
		$files = [];
		foreach (Finder::create()->files()->name('*.yml')->in($dir) as $file) {
			/** @var SplFileInfo $file */
			$files[] = $file->getPathname();
		}
		
		// Sort the file list
		sort($files);
		
		// Process all Yaml configuration files of the app
		foreach ($files as $file) {
			$manip = new ConfigurationManipulator($file);
			$manip->updateConfigurationForMultikernel();
		}
	}
	
	/**
	 * Reconfigure composer.json for the BootKernel.
	 *
	 * @param string $dir App configuration directory
	 * @return void
	 */
	private function updateComposerJson($dir)
	{
		// Output the headline
		$this->output->writeln('> Updating <info>composer.json</info>');
		
		// Get the manipulator for composer.json
		$manip = new ComposerManipulator($dir . '/composer.json');
		
		// Get the current incenteev-parameters setting
		$parameterFiles = $manip->getParameterFiles();
		
		// Remove all kernel and cache classes from the classmap
		$manip->removeFromClassmap(array_map(function(SplFileInfo $file) {
			return $file->getRelativePathname();
		}, array_merge($this->kernelClassFiles, $this->cacheClassFiles)));
		
		// Add the requirement for the motana/multikernel package if required
		$manip->addRequirements([
			'motana/multikernel' => '~1.3',
		]);
		
		// Add the boot kernel to the classmap
		$manip->addToClassMap([
			'apps/BootKernel.php'
		]);
		
		// Remove all files from the incenteev-parameters setting
		$manip->removeParameterFiles($parameterFiles);
		
		// Add the parameters.yml of the boot kernel to the incenteev-parameters
		// setting, as well as the previously listed files with their new paths
		$manip->addParameterFiles(array_merge([
			'apps/config/parameters.yml',
		], array_map(function($file) {
			return 'apps/' . $file;
		}, $parameterFiles)));
		
		// Save the composer.json and update the hash in composer.lock
		$manip->save();
		
		// Output a blank line
		$this->output->write("\n");

		// Add messages for the summary
		if ($manip->hasChangedRequirements()) {
			$this->messages[] = 'The <info>motana/multikernel</info> package has been added to the require section of composer.json.';
			$this->messages[] = 'You should run <comment>composer update</comment> to update your dependencies.';
		} else {
			$this->messages[] = 'The autoloader classmap in composer.json has been updated.';
			$this->messages[] = 'You should run <comment>composer dump-autoload</comment>';
		}
	}
	
	/**
	 * Clean up the project directory after the modifications.
	 *
	 * @param string $dir Project directory
	 * @return void
	 */
	private function cleanupProjectDirectory($dir)
	{
		// Output the headline
		$this->output->writeln('> Cleaning up working directory');
		
		// Get the manipulator for the filesystem
		$filesystem = new FilesystemManipulator();
		
		// Remove the app directories
		foreach ($this->kernelClassFiles as $file) {
			/** @var SplFileInfo $file */
			$filesystem->remove($file->getPath());
		}
		
		// Remove the var/cache, var/logs and var/sessions directories
		foreach ([ 'cache', 'logs', 'sessions' ] as $subdir) {
			if (is_dir($path = $dir . '/var/' . $subdir)) {
				$files = iterator_to_array(Finder::create()->notName('.gitkeep')->in($path));
				if ( ! empty($files)) {
					$filesystem->remove(array_map(function($file) {
						return $file->getPathname();
					}, $files));
				}
			}
		}
	}
}

/**
 * Scope isolated include.
 * Prevents access to $this/self from included files.
 *
 * @codeCoverageIgnore
 */
function includeFile($file)
{
	include $file;
}
