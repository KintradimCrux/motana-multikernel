<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Generator\Model;

use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

use Sensio\Bundle\GeneratorBundle\Model\Bundle;

/**
 * Represents an app including bundle being built.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class App extends Bundle
{
	/**
	 * Project directory.
	 *
	 * @var string
	 */
	private $projectDirectory;
	
	/**
	 * App kernel name.
	 *
	 * @var string
	 */
	private $kernelName;
	
	/**
	 * Boolean indicating that a bundle should be generated for the app.
	 *
	 * @var boolean
	 */
	private $generateBundle;
	
	/**
	 * Boolean indicating that a microkernel app should be generated.
	 *
	 * @var boolean
	 */
	private $generateMicrokernel;
	
	/**
	 * Boolean indicating that a multikernel app should be generated.
	 *
	 * @var boolean
	 */
	private $multikernel;
	
	/**
	 * Constructor.
	 *
	 * @param string $projectDirectory Project directory
	 * @param string $kernelName Kernel name
	 * @param boolean $multikernel Boolean indicating that a multikernel app should be generated
	 * @param boolean $generateBundle Boolean indicating whether a bundle for the application should be generated or not
	 * @param string $bundleNamespace Application bundle namespace
	 * @param string $bundleName Application bundle name
	 * @param string $bundleTargetDirectory Target directory where to create the bundle
	 * @param string $bundleConfigFormat Bundle configuration format (php, xml, yml, or annotation)
	 */
	public function __construct($projectDirectory, $kernelName, $multikernel, $generateBundle, $bundleNamespace = null,
			$bundleName = null, $bundleTargetDirectory = null, $bundleConfigFormat = null, $generateMicroKernel = false)
	{
		// Initialize properties
		$this->kernelName = BootKernel::sanitizeKernelName($kernelName);
		$this->projectDirectory = $projectDirectory;
		$this->generateBundle = $generateBundle;
		$this->multikernel = $multikernel;
		$this->generateMicrokernel = $generateMicroKernel;
		
		// Call parent constructor
		parent::__construct($bundleNamespace, $bundleName, $bundleTargetDirectory, $bundleConfigFormat, false);
		
		// Override tests directory
		$this->setTestsDirectory($projectDirectory . '/tests');
	}
	
	/**
	 * Returns the project directory.
	 *
	 * @return string
	 */
	public function getProjectDirectory()
	{
		return $this->projectDirectory;
	}
	
	/**
	 * Returns the application directory.
	 *
	 * @return string
	 */
	public function getAppDirectory()
	{
		return $this->projectDirectory . ($this->multikernel ? '/apps/' : '/') . $this->kernelName;
	}
	
	/**
	 * Returns the kernel name.
	 *
	 * @return string
	 */
	public function getKernelName()
	{
		return $this->kernelName;
	}
	
	/**
	 * Returns the kernel class name for the app.
	 *
	 * @return string
	 */
	public function getKernelClassName()
	{
		// Return the kernel class name
		return BootKernel::camelizeKernelName($this->kernelName) . 'Kernel';
	}
	
	/**
	 * Returns the cache class name for the app.
	 *
	 * @return string
	 */
	public function getCacheClassName()
	{
		// Return the cache class name
		return BootKernel::camelizeKernelName($this->kernelName) . 'Cache';
	}
	
	/**
	 * Returns a boolean indicating whether a bundle for the app should be generated or not.
	 *
	 * @return boolean
	 */
	public function shouldGenerateBundle()
	{
		return $this->generateBundle;
	}
	
	/**
	 * Returns a boolean indicating whether a microkernel should be generated or not.
	 *
	 * @return boolean
	 */
	public function shouldGenerateMicrokernel()
	{
		return $this->generateMicrokernel;
	}
	
	/**
	 * Returns a boolean indicating whether a multikernel app or should be generated or not.
	 *
	 * @return boolean
	 */
	public function shouldGenerateMultikernel()
	{
		return $this->multikernel;
	}
}
