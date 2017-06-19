<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

/**
 * Add kernel data for the BootKernel to cache.
 * 
 * @author torr
 */
class AddKernelsToCachePass implements CompilerPassInterface
{
	/**
	 * The DelegateKernel to process.
	 * 
	 * @var BootKernel
	 */
	private $kernel;
	
	/**
	 * Constructor.
	 * 
	 * @param BootKernel $kernel A BootKernel instance
	 */
	public function __construct(BootKernel $kernel)
	{
		$this->kernel = $kernel;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface::process()
	 */
	public function process(ContainerBuilder $container)
	{
		$rootDir = $this->kernel->getRootDir();
		
		$files = iterator_to_array(Finder::create()
			->files()
			->depth(1)
			->name('*Kernel.php')
			->in($rootDir)
		);
		
		$data = array();
		foreach ($files as $path => $file) {
			/** @var SplFileInfo $file */
			$kernelName = dirname($file->getRelativePathname());
			$cache = current(iterator_to_array(Finder::create()
				->files()
				->depth(1)
				->name(str_replace('Kernel', 'Cache', $file->getBasename()))
				->in($rootDir)
			));
			/** @var SplFileInfo $cache */
			$data[$kernelName] = array(
				'kernel' => $file->getRelativePathname(),
				'cache' => is_object($cache) ? $cache->getRelativePathname() : $cache,
			);
		}
		
		ksort($data);
		
		$this->kernel->setKernelData($data);
	}
}
