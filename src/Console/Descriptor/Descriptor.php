<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Descriptor;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Extension for the Symfony Descriptor base class.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class Descriptor implements ContainerAwareInterface, DescriptorInterface
{
	use ContainerAwareTrait;
	
	/**
	 * Where output is written to.
	 *
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\DescriptorInterface::describe()
	 */
	public function describe(OutputInterface $output, $object, array $options = [])
	{
		$this->output = $output;
		
		switch (true) {
			case $object instanceof InputArgument:
				$this->describeInputArgument($object, $options);
				break;
			case $object instanceof InputOption:
				$this->describeInputOption($object, $options);
				break;
			case $object instanceof InputDefinition:
				$this->describeInputDefinition($object, $options);
				break;
			case $object instanceof Command:
				$this->describeCommand($object, $options);
				break;
			case $object instanceof Application:
				$this->describeApplication($object, $options);
				break;
			default:
				throw new InvalidArgumentException(sprintf('Object of type "%s" is not describable.', get_class($object)));
		}
	}
	
	/**
	 * Writes content to output.
	 *
	 * @param string $content
	 * @param bool   $decorated
	 */
	protected function write($content, $decorated = false)
	{
		$this->output->write($content, false, $decorated ? OutputInterface::OUTPUT_NORMAL : OutputInterface::OUTPUT_RAW);
	}
	
	/**
	 * Describes an InputArgument instance.
	 *
	 * @return string|mixed
	 */
	abstract protected function describeInputArgument(InputArgument $argument, array $options = []);
	
	/**
	 * Describes an InputOption instance.
	 *
	 * @return string|mixed
	 */
	abstract protected function describeInputOption(InputOption $option, array $options = []);
	
	/**
	 * Describes an InputDefinition instance.
	 *
	 * @return string|mixed
	 */
	abstract protected function describeInputDefinition(InputDefinition $definition, array $options = []);
	
	/**
	 * Describes a Command instance.
	 *
	 * @return string|mixed
	 */
	abstract protected function describeCommand(Command $command, array $options = []);
	
	/**
	 * Describes an Application instance.
	 *
	 * @return string|mixed
	 */
	abstract protected function describeApplication(Application $application, array $options = []);
	
	/**
	 * Returns the processed help for a command.
	 *
	 * @param Command $command Command to inspect
	 * @return string
	 */
	protected function getProcessedHelp(Command $command)
	{
		return rtrim(str_replace('php ' . $_SERVER['PHP_SELF'], $this->makePathRelative($_SERVER['PHP_SELF']), $command->getProcessedHelp()));
	}
	
	/**
	 * Tries to make a path relative to the project, which prints nicer.
	 *
	 * @param string $absolutePath
	 *
	 * @return string
	 */
	public function makePathRelative($absolutePath)
	{
		$projectRootDir = $this->container->getParameter('kernel.project_dir');
		
		return str_replace($projectRootDir, '.', realpath($absolutePath) ?: $absolutePath);
	}
}
