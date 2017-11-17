<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Manipulator;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Iterator\CustomFilterIterator;
use Symfony\Component\Finder\Iterator\SortableIterator;

/**
 * Changes the project filesystem.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class FilesystemManipulator extends Filesystem
{
	/**
	 * Output instance used to print messages.
	 *
	 * @var ConsoleOutput
	 */
	private static $output;

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Filesystem\Filesystem::copy()
	 */
	public function copy($originFile, $targetFile, $overwriteNewerFiles = false)
	{
		// Check the specified file exists
		if (stream_is_local($originFile) && ! is_file($originFile)) {
			throw new FileNotFoundException(sprintf('Failed to copy "%s" because file does not exist.', $originFile), 0, null, $originFile);
		}

		// Create target directory if required
		$this->mkdir(dirname($targetFile));

		// Check the file previously existed
		$doCopy = true;
		$previouslyExisted = is_file($targetFile);
		if ( ! $overwriteNewerFiles && null === parse_url($originFile, PHP_URL_HOST) && $previouslyExisted) {
			$doCopy = filemtime($originFile) > filemtime($targetFile);
		}

		// File does not exist or is not up to date
		if ($doCopy)
		{
			// Copy the file
			parent::copy($originFile, $targetFile, $overwriteNewerFiles);

			// Output a message indicating what happened
			if ($previouslyExisted) {
				self::write(sprintf("  <fg=yellow>updated</> %s\n", self::relativizePath($targetFile)));
			} else {
				self::write(sprintf("  <fg=green>created</> %s\n", self::relativizePath($targetFile)));
			}
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Filesystem\Filesystem::dumpFile()
	 */
	public function dumpFile($filename, $content)
	{
		// Check the file previously existed
		$previouslyExisted = is_file($filename);

		// Write the file
		parent::dumpFile($filename, $content);

		// Output a message indicating what happened
		if ($previouslyExisted) {
			self::write(sprintf("  <fg=yellow>updated</> %s\n", self::relativizePath($filename)));
		} else {
			self::write(sprintf("  <fg=green>created</> %s\n", self::relativizePath($filename)));
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Filesystem\Filesystem::mirror()
	 */
	public function mirror($originDir, $targetDir, \Traversable $iterator = null, $options = [])
	{
		// No iterator specified, create one only skipping '.' and '..'
		if (null === $iterator)
		{
			// Create a recursive iterator over the origin directory
			$innerIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($originDir), \RecursiveIteratorIterator::SELF_FIRST);
			
			// Filter out '.' and '..' on the recursive iterator
			$filterIterator = new CustomFilterIterator($innerIterator, [
				function(\SplFileInfo $file) {
					return ! in_array($file->getBasename(), ['.', '..']);
				}
			]);
			
			// Sort the result of the filter iterator
			$iterator = new SortableIterator($filterIterator, SortableIterator::SORT_BY_NAME);
		}

		// Mirror the directory
		parent::mirror($originDir, $targetDir, $iterator, $options);
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Filesystem\Filesystem::mkdir()
	 */
	public function mkdir($dirs, $mode = 0777)
	{
		// Convert dirs parameter to array
		$dirs = $dirs instanceof \Traversable ? iterator_to_array($dirs) : (array) $dirs;

		// Process all specified directories
		foreach ($dirs as $dir)
		{
			// Skip already existing directories
			if (is_dir($dir)) {
				continue;
			}

			// Create the directory
			parent::mkdir($dir, $mode);

			// Output a message indicating what happened
			self::write(sprintf("  <fg=green>created</> %s\n", self::relativizePath($dir)));
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Filesystem\Filesystem::remove()
	 */
	public function remove($files)
	{
		// Convert dirs parameter to array
		$files = $files instanceof \Traversable ? iterator_to_array($files) : (array) $files;

		// Sort the files in reverse order
		krsort($files);
		
		// Remove all files and directories in the list (in reverse order)
		foreach ($files as $file)
		{
			// Generate the suffix for the message
			$suffix = is_dir($file) ? '/' : '';

			// Remove the file
			parent::remove($file);

			// Output a message indicating what happened
			self::write(sprintf("  <fg=red>removed</> %s%s\n", self::relativizePath($file), $suffix));
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Filesystem\Filesystem::symlink()
	 */
	public function symlink($originDir, $targetDir, $copyOnWindows = false)
	{
		// Check the symlink previously existed
		$previouslyExisted = file_exists($targetDir);

		// Create or update the symlink
		parent::symlink($originDir, $targetDir, $copyOnWindows);

		// Output a message indicating what happened
		if ($previouslyExisted) {
			self::write(sprintf("  <fg=yellow>updated</> %s\n", self::relativizePath($targetDir)));
		} else {
			self::write(sprintf("  <fg=green>created</> %s\n", self::relativizePath($targetDir)));
		}
	}

	/**
	 * Makes an absolute path relative to the current working directory.
	 *
	 * @param string $absolutePath Path to convert
	 * @return string|mixed
	 */
	private static function relativizePath($absolutePath)
	{
		// Make the path relative
		$relativePath = str_replace(getcwd(), '.', $absolutePath);

		// Return the path, append slash to directories
		return is_dir($absolutePath) ? rtrim($relativePath, '/') . '/' : $relativePath;
	}

	/**
	 * Output a message.
	 *
	 * @param string $message
	 */
	private static function write($message)
	{
		// Create a new output if required
		if (null === self::$output) {
			self::$output = new ConsoleOutput();
		}

		// Output the message
		self::$output->write($message);
	}
}
