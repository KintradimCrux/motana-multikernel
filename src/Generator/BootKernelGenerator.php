<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Generator;

use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Generator for a boot kernel.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class BootKernelGenerator extends Generator
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
	 * Generate the apps directory containing a BootKernel and its configuration.
	 *
	 * @param array $kernels Available kernel names
	 * @param string $dir Directory where the filesystem structure is created; if none is specified, the current working directory is used
	 * @param string $kernelClassName Class name for the kernel to use instead of the default ("BootKernel")
	 */
	public function generateBootKernel(array $kernels, $dir = null, $kernelClassName = null)
	{
		if (null === $dir) {
			$dir = getcwd();
		}
		
		$kernelClassName = $kernelClassName ?: 'BootKernel';
		
		// Parameters for twig
		$parameters = [
			'kernel_base_class' => BootKernel::class,
			'kernel_base_class_alias' => 'AbstractKernel',
			'kernel_class_name' => $kernelClassName,
			'multikernel' => true,
		];
		
		// Generate .htaccess first
		$this->renderFile('apps/htaccess.twig', $dir . '/apps/.htaccess', $parameters);
		
		// Generate autoload.php and boot kernel
		$this->renderFile('apps/autoload.php.twig', $dir . '/apps/autoload.php', $parameters);
		$this->renderFile('apps/BootKernel.php.twig', $dir . '/apps/' . $kernelClassName . '.php', $parameters);
		
		// Generate boot kernel configuration
		$this->renderFile('apps/config.yml.twig', $dir . '/apps/config/config.yml', $parameters);
		$this->renderFile('apps/config_dev.yml.twig', $dir . '/apps/config/config_dev.yml', $parameters);
		$this->renderFile('apps/config_prod.yml.twig', $dir . '/apps/config/config_prod.yml', $parameters);
		$this->renderFile('apps/config_test.yml.twig', $dir . '/apps/config/config_test.yml', $parameters);
		$this->renderFile('apps/parameters.yml.dist.twig', $dir . '/apps/config/parameters.yml.dist', $parameters);
		
		// Generate parameters.yml if there is only one kernel
		if (1 === count($kernels)) {
			$parameters['default_kernel'] = current($kernels);
			$this->renderFile('apps/parameters.yml.twig', $dir . '/apps/config/parameters.yml', $parameters);
		}
		
		// Generate new bin/console
		$this->renderFile('apps/console.php.twig', $dir . '/bin/console', $parameters);
		$this->filesystem->chmod($dir . '/bin/console', 0755);
		
		// Generate new front controller scripts
		$this->renderFile('apps/app.php.twig', $dir . '/web/app.php', $parameters);
		$this->renderFile('apps/app_dev.php.twig', $dir . '/web/app_dev.php', $parameters);
	}
}
