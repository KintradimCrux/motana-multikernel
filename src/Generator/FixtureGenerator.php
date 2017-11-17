<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Generator for content used during tests.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class FixtureGenerator extends Generator
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->setSkeletonDirs([
			__DIR__ . '/../Resources/fixtures',
			__DIR__ . '/../Resources/skeleton',
		]);
	}

	/**
	 * Generate a configuration file from a template.
	 *
	 * @param string $template Template name
	 * @param string $target Target filename
	 * @param array $parameters Twig template parameters
	 */
	public function generateConfig($template, $target, array $parameters = [])
	{
		// Render the template and write output to the target file, Twig will check if the template exists
		$this->renderFile($template, $target, array_merge([
			'kernel' => 'null',
			'secret' => $this->generateRandomSecret(),
		], $parameters));
	}
	
	/**
	 * Generates the output expected from a command.
	 *
	 * @param string $templateName Template name
	 * @param string $format Output format (json | md | txt | xml)
	 * @param array $parameters Twig template parameters
	 * @return string
	 */
	public function generateCommandOutput($templateName, $format, array $parameters= [])
	{
		// Get the template path
		$file = sprintf('commands/%s/%s/%s.%s.twig', $parameters['command_name'], $format, $templateName, $format);
		
		// Template exists, render and return its content
		if (is_file(__DIR__ . '/../Resources/fixtures/' . $file)) {
			return $this->render($file, array_merge([
				'fixture_dir' => getenv('__MULTIKERNEL_FIXTURE_DIR'),
				'kernel_version' => Kernel::VERSION,
			], $parameters));
		}
		
		// Return an empty string when no template was found
		return '';
	}
	
	/**
	 * Generates the output expected from a descriptor.
	 *
	 * @param string $templateName Template name
	 * @param string $format Output format (json | md | txt | xml)
	 * @param array $parameters Twig template parameters
	 * @return string
	 */
	public function generateDescriptorOutput($templateName, $format, array $parameters = [])
	{
		// Get the template path
		$file = sprintf('descriptor/%s/%s.%s.twig', $format, $templateName, $format);
		
		// Template exists, render and return its content
		if (is_file(__DIR__ . '/../Resources/fixtures/' . $file)) {
			return $this->render($file, array_merge([
				'kernel_version' => Kernel::VERSION
			], $parameters));
		}
		
		// Return an empty string when no template was found
		return '';
	}
	
	/**
	 * Generate a minimalist kernel class file.
	 *
	 * @param string $filename Filename of the file to generate
	 * @param string $className Class name
	 */
	public function generateEmptyKernelClass($filename, $className)
	{
		$this->renderFile('EmptyAppKernel.php.twig', $filename, [
			'kernel_class_name' => $className,
		]);
	}
	
	/**
	 * Generate a kernel class file.
	 *
	 * @param string $filename Filename of the file to generate
	 * @param string $className Class name
	 * @param array $parameters Parameters for twig template
	 */
	public function generateKernelClass($filename, $className, array $parameters = [])
	{
		$this->renderFile('app/AppKernel.php.twig', $filename, array_merge([
			'kernel_base_class' => Kernel::class,
			'kernel_base_class_short' => 'Kernel',
			'kernel_class_name' => $className,
		], $parameters));
	}
	
	/**
	 * Generate a cache class file.
	 *
	 * @param string $filename Filename of the file to generate
	 * @param string $className Class name
	 */
	public function generateCacheClass($filename, $className)
	{
		$this->renderFile('app/AppCache.php.twig', $filename, [
			'cache_base_class' => HttpCache::class,
			'cache_base_class_short' => 'HttpCache',
			'cache_class_name' => $className,
		]);
	}
	
	/**
	 * Generates a good random value for Symfony's 'secret' option.
	 *
	 * @author Fabien Potencier <fabien@symfony.com>
	 * @return string The randomly generated secret
	 */
	public function generateRandomSecret()
	{
		return hash('sha1', function_exists('openssl_random_pseudo_bytes') ? openssl_random_pseudo_bytes(23) : uniqid(mt_rand(), true));
	}
}
