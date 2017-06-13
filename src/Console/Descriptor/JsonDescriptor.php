<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Console\Descriptor;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Motana\Bundle\MultiKernelBundle\Console\MultiKernelApplication;

/**
 * A replacement for the Symfony Standard Edition json descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class JsonDescriptor extends Descriptor
{
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputArgument()
	 */
	protected function describeInputArgument(InputArgument $argument, array $options = array())
	{
		$this->writeData($this->getInputArgumentData($argument), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputOption()
	 */
	protected function describeInputOption(InputOption $option, array $options = array())
	{
		$this->writeData($this->getInputOptionData($option), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputDefinition()
	 */
	protected function describeInputDefinition(InputDefinition $definition, array $options = array())
	{
		$this->writeData($this->getInputDefinitionData($definition), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeCommand()
	 */
	protected function describeCommand(Command $command, array $options = array())
	{
		$this->writeData($this->getCommandData($command), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeApplication()
	 */
	protected function describeApplication(Application $application, array $options = array())
	{
		$describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
		$description = new ApplicationDescription($application, $describedNamespace);
		
		if ($application instanceof MultiKernelApplication) {
			$kernels = array_keys($application->getKernel()->getKernels());
		}
		
		$commands = array();
		foreach ($description->getCommands() as $command) {
			$commands[] = $this->getCommandData($command);
		}
		
		if ($application instanceof MultiKernelApplication) {
			$data = $describedNamespace
				? array('kernels' => $kernels, 'commands' => $commands, 'namespace' => $describedNamespace)
				: array('kernels' => $kernels, 'commands' => $commands, 'namespaces' => array_values($description->getNamespaces()));
		} else {
			$data = $describedNamespace
				? array('commands' => $commands, 'namespace' => $describedNamespace)
				: array('commands' => $commands, 'namespaces' => array_values($description->getNamespaces()));
		}
		
		$this->writeData($data, $options);
	}
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Returns data for an InputArgument instance.
	 * 
	 * @param InputArgument $argument Argument to describe
	 * @return array
	 */
	private function getInputArgumentData(InputArgument $argument)
	{
		return array(
			'name' => $argument->getName(),
			'is_required' => $argument->isRequired(),
			'is_array' => $argument->isArray(),
			'description' => preg_replace('#\s*[\r\n]\s*#',' ', $argument->getDescription()),
			'default' => $argument->getDefault(),
		);
	}
	
	/**
	 * Returns data for an InputOption instance.
	 * 
	 * @param InputOption $option Option to describe
	 * @return array
	 */
	private function getInputOptionData(InputOption $option)
	{
		return array(
			'name' => '--' . $option->getName(),
			'shortcut' => $option->getShortcut() ? '-' . implode('|-', explode('|', $option->getShortcut())) : '',
			'accept_value' => $option->acceptValue(),
			'is_value_required' => $option->isValueRequired(),
			'is_multiple' => $option->isArray(),
			'description' => preg_replace('#\s*[\r\n]\s*#',' ', $option->getDescription()),
			'default' => $option->getDefault(),
		);
	}
	
	/**
	 * Returns data for an InputDefinition instance.
	 * 
	 * @param InputDefinition $definition Definition to describe
	 * @return array
	 */
	private function getInputDefinitionData(InputDefinition $definition)
	{
		$arguments = array();
		foreach ($definition->getArguments() as $name => $argument) {
			if ('command' !== $name) {
				$arguments[$name] = $this->getInputArgumentData($argument);
			}
		}
		
		$options = array();
		foreach ($definition->getOptions() as $name => $option) {
			$options[$name] = $this->getInputOptionData($option);
		}
		
		return array(
			'arguments' => $arguments,
			'options' => $options,
		);
	}
	
	/**
	 * Returns data for a Command instance.
	 * 
	 * @param Command $command Command to describe
	 * @return array
	 */
	private function getCommandData(Command $command)
	{
		$kernel = $command->getApplication() instanceof MultiKernelApplication ? null : $command->getApplication()->getKernel()->getName();
		
		$command->getSynopsis();
		$command->mergeApplicationDefinition(false);
		
		$usages = array();
		if ( ! $kernel) {
			$usages[] = $_SERVER['PHP_SELF'].' '.$command->getSynopsis(true);
		}
		
		foreach (array_merge(array($command->getSynopsis()), $command->getUsages(), $command->getAliases()) as $usage) {
			$usages[] = $_SERVER['PHP_SELF'].' '.($kernel ? $kernel : '<kernel>').' '.$usage;
		}
		
		return array(
			'name' => $command->getName(),
			'usage' => $usages,
			'description' => $command->getDescription(),
			'help' => $this->getProcessedHelp($command),
			'definition' => $this->getInputDefinitionData($command->getNativeDefinition()),
		);
	}
	
	/**
	 * Output data in JSON format.
	 * 
	 * @param array $data Data to output
	 * @param array $options Display options
	 */
	private function writeData(array $data, array $options = array())
	{
		$this->write(json_encode($data, JSON_PRETTY_PRINT) . "\n");
	}
	
	// }}}
}
