<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Manipulator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Manipulator\ConfigurationManipulator as BaseConfigurationManipulator;

use Symfony\Component\Yaml\Yaml;

/**
 * Changes the content of a YAML configuration file.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ConfigurationManipulator extends BaseConfigurationManipulator
{
	/**
	 * Path of the configuration file to process.
	 *
	 * @var string
	 */
	private $file;
	
	/**
	 * Content of the original configuration file.
	 *
	 * @var string
	 */
	private $content;
	
	/**
	 * Processed content.
	 *
	 * @var string
	 */
	private $processedContent;
	
	/**
	 * Parsed configuration.
	 *
	 * @var array
	 */
	private $config;
	
	/**
	 * Constructor.
	 *
	 * @param string $file The YAML configuration file path
	 */
	public function __construct($file)
	{
		// Initialize properties
		$this->file = $file;
		
		// Load the file if it exists
		if (is_file($file)) {
			$this->content = file_get_contents($this->file);
			$this->processedContent = $this->content;
			$this->config = Yaml::parse($this->content);
		}
		
		// Call parent constructor
		parent::__construct($file);
	}
	
	/**
	 * Reconfigures settings in the Yaml configuration files of an app for the
	 * changed filesystem structure of a multikernel project.
	 *
	 * @return boolean
	 */
	public function updateConfigurationForMultikernel()
	{
		// File does not exist, just return
		if ( ! is_file($this->file)) {
			return false;
		}
		
		// Detect the environment from the filename
		$environment = null;
		$basename = basename($this->file, '.yml');
		if (false !== strpos($basename, '_')) {
			$parts = explode('_', $basename);
			$basename = reset($parts);
			$environment = end($parts);
		}
		
		// Process configuration
		switch ($basename) {
			case 'config':
				$this->updateAppConfiguration($environment);
				break;
				
			case 'services':
				$this->updateServiceConfiguration();
				break;
		}
		
		// Write the file if its content has changed
		if ($this->processedContent !== $this->content) {
			Generator::dump($this->file, $this->processedContent);
		}
		
		// Return success
		return true;
	}
	
	/**
	 * Configure the path to routing.yml and the session save path in config.yml.
	 *
	 * @param string $environment Environment contained in the filename (if any)
	 * @return void
	 */
	private function updateAppConfiguration($environment = null)
	{
		// Get the routing configuration filename
		$filename = 'routing' . ($environment ? '_' . $environment : '') . '.yml';
		
		// Correct router resource path in framework configuration
		if (isset($this->config['framework']['router']['resource'])) {
			$this->processedContent = str_replace(
				"resource: '" . $this->config['framework']['router']['resource'] . "'",
				"resource: '%kernel.project_dir%/apps/%kernel.name%/config/" . $filename . "'",
				$this->processedContent
			);
		}
		
		// Correct session save path in framework configuration
		if (isset($this->config['framework']['session']['save_path'])) {
			$this->processedContent = str_replace(
				"save_path: '" . $this->config['framework']['session']['save_path'] . "'",
				"save_path: '%kernel.project_dir%/var/sessions/%kernel.name%/%kernel.environment%'",
				$this->processedContent
			);
		}
	}
	
	/**
	 * Configure the path to the src/ directory in services.yml.
	 *
	 * @return void
	 */
	private function updateServiceConfiguration()
	{
		// Process all services
		foreach ($this->config['services'] as $classOrNamespace => $section)
		{
			// Correct resource path in services configuration
			if (isset($section['resource']) && false !== strpos($section['resource'], '../src')) {
				$this->processedContent = str_replace(
					"resource: '" . $section['resource'] . "'",
					"resource: '" . preg_replace('|^(\.\./)+src|', '%kernel.project_dir%/src', $section['resource']) . "'",
					$this->processedContent
				);
			}
			
			// Correct exclude path in services configuration
			if (isset($section['exclude']) && false !== strpos($section['exclude'], '../src')) {
				$this->processedContent = str_replace(
					"exclude: '" . $section['exclude'] . "'",
					"exclude: '" . preg_replace('|^(\.\./)+src|', '%kernel.project_dir%/src', $section['exclude']) . "'",
					$this->processedContent
				);
			}
		}
	}
}
