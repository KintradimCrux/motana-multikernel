<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 * Portions (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Motana\Bundle\MultikernelBundle\Manipulator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Manipulator\Manipulator;

/**
 * Changes requirements, autoloader classmap and incenteev-parameters in composer.json.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ComposerManipulator extends Manipulator
{
	/**
	 * Path of composer.json.
	 *
	 * @var string
	 */
	private $file;
	
	/**
	 * Parsed configuration.
	 *
	 * @var array
	 */
	private $config;
	
	/**
	 * Boolean indicating whether the require or require-dev section of
	 * the composer.json have changed or not.
	 *
	 * @var boolean
	 */
	private $requirementsChanged;
	
	/**
	 * Constructor.
	 *
	 * @param string $file Path to composer.json
	 */
	public function __construct($file)
	{
		$this->file = $file;
		$this->config = json_decode(file_get_contents($this->file), true);
		$this->requirementsChanged = false;
	}
	
	/**
	 * Returns a boolean indicating whether the require or require-dev
	 * sections of composer.section have been changed or not.
	 *
	 * @return boolean
	 */
	public function hasChangedRequirements()
	{
		return $this->requirementsChanged;
	}
	
	/**
	 * Add packages to the require section of composer.json.
	 *
	 * @param array $packages Associative array (packageName => version) of packages to add
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
	 */
	public function addRequirements(array $packages)
	{
		// Initialize with empty array if required
		if ( ! isset($this->config['require'])) {
			$this->config['require'] = [];
		}
		
		// Process specified packages
		foreach ($packages as $packageName => $version)
		{
			// Skip packages already listed with the same version
			if (isset($this->config['require'][$packageName]) && $version === $this->config['require'][$packageName]) {
				continue;
			}
			
			// Update requirements
			$this->config['require'][$packageName] = $version;
			
			// Requirements have changed
			$this->requirementsChanged = true;
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Add packages to the require-dev section of composer.json.
	 *
	 * @param array $packages Associative array (packageName => version) of packages to add
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
	 */
	public function addDevRequirements(array $packages)
	{
		// Initialize with empty array if required
		if ( ! isset($this->config['require-dev'])) {
			$this->config['require-dev'] = [];
		}
		
		// Process specified packages
		foreach ($packages as $packageName => $version)
		{
			// Skip packages already listed with the same version
			if (isset($this->config['require-dev'][$packageName]) && $version === $this->config['require-dev'][$packageName]) {
				continue;
			}
			
			// Update dev requirements
			$this->config['require-dev'][$packageName] = $version;
			
			// Requirements have changed
			$this->requirementsChanged = true;
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Removes a list of filenames from the autoload classmap.
	 *
	 * @param array $files Files to remove
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
	 */
	public function removeFromClassmap(array $files)
	{
		// Config contains an autoloader classmap
		if (isset($this->config['autoload']['classmap']))
		{
			// Remove files from autoloader classmap
			foreach ($files as $file) {
				if (false !== $i = array_search($file, $this->config['autoload']['classmap'])) {
					array_splice($this->config['autoload']['classmap'], $i, 1, []);
				}
			}
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Add a list of files to the autoload classmap.
	 *
	 * @param array $files Files to add
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
	 */
	public function addToClassmap(array $files)
	{
		// Initialize autoloader clasmap with empty array if required
		if ( ! isset($this->config['autoload']['classmap'])) {
			$this->config['autoload']['classmap'] = [];
		}
		
		// Add files to autoloader classmap
		foreach ($files as $file) {
			if ( ! in_array($file, $this->config['autoload']['classmap'])) {
				$this->config['autoload']['classmap'][] = $file;
			}
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Returns the files listed in incenteev-parameters.
	 *
	 * @return array
	 */
	public function getParameterFiles()
	{
		$parameterFiles = [];
		
		// Configuration contains incenteev-parameters
		if (isset($this->config['extra']['incenteev-parameters']))
		{
			// Convert flat array to multidimensional array if required
			if (isset($this->config['extra']['incenteev-parameters']['file'])) {
				$this->config['extra']['incenteev-parameters'] = [ $this->config['extra']['incenteev-parameters'] ];
			}
			
			// Add all files listed in incenteev-parameters to the list
			foreach ($this->config['extra']['incenteev-parameters'] as $index => $record) {
				$parameterFiles[] = $record['file'];
			}
		}
		
		// Return the file list
		return $parameterFiles;
	}
	
	/**
	 * Removes a list of files from incenteev-parameters.
	 *
	 * @param array $files Files to remove
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
	 */
	public function removeParameterFiles(array $files)
	{
		// Configuration contains incenteev-parameters
		if (isset($this->config['extra']['incenteev-parameters']))
		{
			// Convert flat array to multidimensional array if required
			if (isset($this->config['extra']['incenteev-parameters']['file'])) {
				$this->config['extra']['incenteev-parameters'] = [ $this->config['extra']['incenteev-parameters'] ];
			}
			
			// Remove specified files from incenteev-parameters
			foreach ($this->config['extra']['incenteev-parameters'] as $index => $record) {
				if (in_array($record['file'], $files)) {
					unset($this->config['extra']['incenteev-parameters'][$index]);
				}
			}
			
			// Re-index the incenteev parameters option
			$this->config['extra']['incenteev-parameters'] = array_slice($this->config['extra']['incenteev-parameters'], 0);
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Add a list of files to incenteev-parameters.
	 *
	 * @param array $files Files to add
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\ComposerManipulator
	 */
	public function addParameterFiles(array $files)
	{
		// Initialize incenteev-parameters with empty array if required
		if ( ! isset($this->config['extra']['incenteev-parameters'])) {
			$this->config['extra']['incenteev-parameters'] = [];
		}
		
		// Convert flat array to multidimensional array if required
		if (isset($this->config['extra']['incenteev-parameters']['file'])) {
			$this->config['extra']['incenteev-parameters'] = [ $this->config['extra']['incenteev-parameters'] ];
		}
		
		// Add specified files to incenteev-parameters
		foreach ($files as $file) {
			$this->config['extra']['incenteev-parameters'][] = [
				'file' => $file,
			];
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Save the processed composer.json.
	 *
	 * @return void
	 */
	public function save()
	{
		// Get the unprocessed and processed file content
		$unprocessedConfig = file_get_contents($this->file);
		$processedConfig = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
		
		// File content has changed
		if ($processedConfig !== $unprocessedConfig)
		{
			// Write composer.json
			Generator::dump($this->getFilename(), $processedConfig);
			
			// Composer requirements have changed and there is a composer.lock
			if ( ! $this->hasChangedRequirements() && is_file($lockFile = dirname($this->file) . '/composer.lock')) {
				$composerLockFileContents = json_decode(file_get_contents($lockFile), true);
				
				// Update hash in composer.lock
				if (array_key_exists('hash', $composerLockFileContents)) {
					$composerLockFileContents['hash'] = md5($processedConfig);
				}
				
				// Update content-hash in composer.lock
				if (array_key_exists('content-hash', $composerLockFileContents)) {
					$composerLockFileContents['content-hash'] = $this->getComposerContentHash();
				}
				
				// Write composer.lock
				Generator::dump($lockFile, json_encode($composerLockFileContents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			}
		}
	}
	
	/**
	 * Calculates the content-hash for composer.lock.
	 *
	 * @author Fabien Potencier <fabien@symfony.com>
	 * @return string
	 */
	private function getComposerContentHash()
	{
		// Relevant keys in composer.json
		$relevantKeys = [
			'name',
			'version',
			'require',
			'require-dev',
			'conflict',
			'replace',
			'provide',
			'minimum-stability',
			'prefer-stable',
			'repositories',
			'extra',
		];

		// Filter composer.json content for relevant keys
		$relevantComposerConfig = [];
		foreach (array_intersect($relevantKeys, array_keys($this->config)) as $key) {
			$relevantComposerConfig[$key] = $this->config[$key];
		}
		
		// Add the platform setting to the config
		if (isset($this->config['config']['platform'])) {
			$relevantComposerConfig['config']['platform'] = $this->config['config']['platform'];
		}
		
		// Sort the config
		ksort($relevantComposerConfig);
		
		// Return a md5 hash over the serialized config
		return md5(json_encode($relevantComposerConfig));
	}
	
	/**
	 * Returns the path to composer.json.
	 *
	 * @return string
	 */
	public function getFilename()
	{
		return $this->file;
	}
}
