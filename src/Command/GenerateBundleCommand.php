<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\GenerateBundleCommand as BaseCommand;
use Sensio\Bundle\GeneratorBundle\Model\Bundle;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * A replacement for the SensioGeneratorBundle GenerateBundleCommand.
 * To be removed when https://github.com/sensiolabs/SensioGeneratorBundle/issues/568 is resolved.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class GenerateBundleCommand extends BaseCommand implements ContainerAwareInterface
{
	use ContainerAwareTrait;
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		// Get the bundles loaded in the kernel
		$bundles = $this->getApplication()->getKernel()->getBundles();
		
		// Command is enabled when the SensioGeneratorBundle is loaded
		return isset($bundles['SensioGeneratorBundle']);
	}
	
	/**
	 * Creates the Bundle object based on the user's (non-interactive) input.
	 *
	 * @param InputInterface $input
	 *
	 * @return Bundle
	 */
	protected function createBundleObject(InputInterface $input)
	{
		foreach ([ 'namespace', 'dir' ] as $option) {
			if (null === $input->getOption($option)) {
				throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
			}
		}
		
		$shared = $input->getOption('shared');
		
		$namespace = Validators::validateBundleNamespace($input->getOption('namespace'), $shared);
		
		if ( ! $bundleName = $input->getOption('bundle-name')) {
			$bundleName = strtr($namespace, [ '\\' => '' ]);
		}
		
		$bundleName = Validators::validateBundleName($bundleName);
		$dir = $input->getOption('dir');
		
		if (null === $input->getOption('format')) {
			$input->setOption('format', 'annotation');
		}
		
		$format = Validators::validateFormat($input->getOption('format'));
		
		// an assumption that the kernel project dir is where the composer.json is
		$projectRootDirectory = $this->getContainer()->getParameter('kernel.project_dir');
		
		if ( ! $this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
			$dir = $projectRootDirectory . '/' . $dir;
		}
		
		// add trailing / if necessary
		$dir = '/' === substr($dir, -1, 1) ? $dir : $dir . '/';
		
		$bundle = new Bundle(
			$namespace,
			$bundleName,
			$dir,
			$format,
			$shared
		);
		
		// not shared - put the tests in the root
		if ( ! $shared) {
			$testsDir = $projectRootDirectory . '/tests/' . $bundleName;
			$bundle->setTestsDirectory($testsDir);
		}
		
		return $bundle;
	}
}
