<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Generator;

use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\Kernel;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Generator for a standard symfony demo app.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class AppGenerator extends Generator
{
	/**
	 * @var Filesystem
	 */
	private $filesystem;
	
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->filesystem = new Filesystem();
	}
	
	/**
	 * Generate an app.
	 *
	 * @param App $app App model
	 * @param array $parameters Parameters for the twig template
	 */
	public function generateApp(App $app, array $parameters = [])
	{
		// Bundles enabled by default
		$bundles = [
			'DoctrineBundle' => true,
			'MotanaMultikernelBundle' => true,
			'SensioDistributionBundle' => true,
			'SensioFrameworkExtraBundle' => true,
			'SwiftmailerBundle' => true,
		];
		
		// Merge bundles parameter
		if (isset($parameters['bundles'])) {
			$bundles = array_merge($bundles, $parameters['bundles']);
			unset($parameters['bundles']);
		}
		
		// Parameters for twig
		$fixture = new FixtureGenerator();
		$parameters = array_merge([
			'secret' => $fixture->generateRandomSecret(),
			'cache_base_class' => HttpCache::class,
			'cache_base_class_short' => 'HttpCache',
			'cache_class_name' => $app->getCacheClassName(),
			'kernel_base_class' => Kernel::class,
			'kernel_base_class_short' => 'Kernel',
			'kernel_class_name' => $app->getKernelClassName(),
			'kernel_name' => $app->getKernelName(),
			'microkernel' => $app->shouldGenerateMicrokernel(),
			'multikernel' => $app->shouldGenerateMultikernel(),
			'namespace' => $app->getNamespace(),
			'bundle' => $app->shouldGenerateBundle() ? $app->getName() : false,
			'bundle_basename' => $app->getBasename(),
			'bundle_path' => substr($app->getTargetDirectory(), strlen($app->getProjectDirectory()) + 1),
			'bundle_shared' => $app->isShared(),
			'format' => $app->getConfigurationFormat(),
			'extension_alias' => $app->getExtensionAlias(),
			'bundles' => $bundles,
		], $parameters);

		// Create .htaccess first
		$this->renderFile('app/htaccess.twig', $app->getAppDirectory() . '/.htaccess', $parameters);
		
		// Generate files required for a Symfony Standard Edition app directory
		if ( ! $app->shouldGenerateMultikernel())
		{
			// Generate autoload.php
			$this->renderFile('apps/autoload.php.twig', $app->getAppDirectory() . '/autoload.php', $parameters);
			
			// Get the project directory
			$dir = dirname($app->getAppDirectory());
			
			// Generate new bin/console
			$this->renderFile('apps/console.php.twig', $dir . '/bin/console', $parameters);
			$this->filesystem->chmod($dir . '/bin/console', 0755);
			
			// Generate new front controller scripts
			$this->renderFile('apps/app.php.twig', $dir . '/web/app.php', $parameters);
			$this->renderFile('apps/app_dev.php.twig', $dir . '/web/app_dev.php', $parameters);
		}
		
		// Generate AppKernel and AppCache classes
		if ($app->shouldGenerateMicrokernel()) {
			$this->renderFile('app/micro/MicroKernel.php.twig', $app->getAppDirectory() . '/' . $app->getKernelClassName() . '.php', $parameters);
		} else {
			$this->renderFile('app/AppCache.php.twig', $app->getAppDirectory() . '/' . $app->getCacheClassName() . '.php', $parameters);
			$this->renderFile('app/AppKernel.php.twig', $app->getAppDirectory() . '/' . $app->getKernelClassName() . '.php', $parameters);
		}

		// Generate configuration
		if ($app->shouldGenerateMicrokernel()) {
			$this->renderFile('app/micro/config.yml.twig', $app->getAppDirectory() . '/config/config.yml', $parameters);
			$this->renderFile('app/micro/config_dev.yml.twig', $app->getAppDirectory() . '/config/config_dev.yml', $parameters);
			$this->renderFile('app/micro/config_prod.yml.twig', $app->getAppDirectory() . '/config/config_prod.yml', $parameters);
			$this->renderFile('app/micro/config_test.yml.twig', $app->getAppDirectory() . '/config/config_test.yml', $parameters);
		} else {
			$this->renderFile('app/config.yml.twig', $app->getAppDirectory() . '/config/config.yml', $parameters);
			$this->renderFile('app/config_dev.yml.twig', $app->getAppDirectory() . '/config/config_dev.yml', $parameters);
			$this->renderFile('app/config_prod.yml.twig', $app->getAppDirectory() . '/config/config_prod.yml', $parameters);
			$this->renderFile('app/config_test.yml.twig', $app->getAppDirectory() . '/config/config_test.yml', $parameters);
			$this->renderFile('app/parameters.yml.dist.twig', $app->getAppDirectory() . '/config/parameters.yml', $parameters);
			unset($parameters['secret']);
			$this->renderFile('app/parameters.yml.dist.twig', $app->getAppDirectory() . '/config/parameters.yml.dist', $parameters);
			$this->renderFile('app/routing.yml.twig', $app->getAppDirectory() . '/config/routing.yml', $parameters);
			$this->renderFile('app/routing_dev.yml.twig', $app->getAppDirectory() . '/config/routing_dev.yml', $parameters);
			$this->renderFile('app/security.yml.twig', $app->getAppDirectory() . '/config/security.yml', $parameters);
			$this->renderFile('app/services.yml.twig', $app->getAppDirectory() . '/config/services.yml', $parameters);
		}
		
		// Generate bundle files
		if ($app->shouldGenerateBundle())
		{
			// Generate template for default controller
			if ($app->shouldGenerateMicrokernel()) {
				$this->renderFile('app/micro/random.html.twig.twig', $app->getAppDirectory() . '/Resources/views/random.html.twig', $parameters);
			} else {
				$this->renderFile('app/base.html.twig.twig', $app->getAppDirectory() . '/Resources/views/base.html.twig', $parameters);
				$this->renderFile('app/index.html.twig.twig', $app->getAppDirectory() . '/Resources/views/default/index.html.twig', $parameters);
			}
			
			// Generate bundle
			$this->renderFile('bundle/Bundle.php.twig', $app->getTargetDirectory(). '/' . $app->getName() . '.php', $parameters);
			
			// Generate default controller
			if ($app->shouldGenerateMicrokernel()) {
				$this->renderFile('app/micro/MicroController.php.twig', $app->getTargetDirectory(). '/Controller/MicroController.php', $parameters);
			} else {
				$this->renderFile('app/DefaultController.php.twig', $app->getTargetDirectory(). '/Controller/DefaultController.php', $parameters);
			}
			
			if ($app->shouldGenerateMicrokernel()) {
				$this->renderFile('app/micro/MicroControllerTest.php.twig', $app->getTestsDirectory() . '/'. $app->getNamespace() .'/Controller/MicroControllerTest.php', $parameters);
			} else {
				// Generate services configuration
				$servicesFilename = $app->getServicesConfigurationFilename();
				$this->renderFile(sprintf('bundle/%s.twig', $servicesFilename), $app->getTargetDirectory() . '/Resources/config/' . $servicesFilename, $parameters);
				
				// Generate routing configuration
				if ($routingFilename = $app->getRoutingConfigurationFilename()) {
					$this->renderFile(sprintf('bundle/%s.twig', $routingFilename), $app->getTargetDirectory() . '/Resources/config/' . $routingFilename, $parameters);
				}
			
				// Generate default controller test
				$this->renderFile('app/DefaultControllerTest.php.twig', $app->getTestsDirectory() . '/'. $app->getNamespace() .'/Controller/DefaultControllerTest.php', $parameters);
			}
		}
	}
}
