<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Jean-François Simon <contact@jfsimon.fr>
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Descriptor;

use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * A replacement for the Symfony Standard Edition json descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class JsonDescriptor extends Descriptor
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputArgument()
	 */
	protected function describeInputArgument(InputArgument $argument, array $options = [])
	{
		$this->writeData($this->getInputArgumentData($argument), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputOption()
	 */
	protected function describeInputOption(InputOption $option, array $options = [])
	{
		$this->writeData($this->getInputOptionData($option), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputDefinition()
	 */
	protected function describeInputDefinition(InputDefinition $definition, array $options = [])
	{
		$this->writeData($this->getInputDefinitionData($definition), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeCommand()
	 */
	protected function describeCommand(Command $command, array $options = [])
	{
		$this->writeData($this->getCommandData($command), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeApplication()
	 */
	protected function describeApplication(Application $application, array $options = [])
	{
		$describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
		$description = new ApplicationDescription($application, $describedNamespace);
		
		if ($application instanceof MultikernelApplication) {
			$kernels = array_keys($application->getKernel()->getKernels());
		}
		
		$commands = $description->getCommands();
		
		$namespaces = $description->getNamespaces();
		
		foreach ($namespaces as $namespaceIndex => $namespace) {
			foreach ($namespace['commands'] as $commandIndex => $commandName) {
				if ( ! isset($commands[$commandName])) {
					unset($namespace['commands'][$commandIndex]);
				}
			}
			
			if (empty($namespace['commands'])) {
				unset($namespaces[$namespaceIndex]);
			}
		}
		
		$commandData = [];
		foreach ($description->getCommands() as $command) {
			$commandData[] = $this->getCommandData($command, false);
		}
		
		if ($application instanceof MultikernelApplication) {
			$data = $describedNamespace
			? [ 'kernels' => $kernels, 'commands' => $commandData, 'namespace' => $describedNamespace ]
			: [ 'kernels' => $kernels, 'commands' => $commandData, 'namespaces' => array_values($namespaces) ];
		} else {
			$data = $describedNamespace
			? [ 'commands' => $commandData, 'namespace' => $describedNamespace ]
			: [ 'commands' => $commandData, 'namespaces' => array_values($namespaces) ];
		}
		
		$this->writeData($data, $options);
	}
	
	/**
	 * Returns data for an InputArgument instance.
	 *
	 * @param InputArgument $argument Argument to describe
	 * @return array
	 */
	private function getInputArgumentData(InputArgument $argument)
	{
		return [
			'name' => $argument->getName(),
			'is_required' => $argument->isRequired(),
			'is_array' => $argument->isArray(),
			'description' => preg_replace('#\s*[\r\n]\s*#',' ', $argument->getDescription()),
			'default' => $argument->getDefault(),
		];
	}
	
	/**
	 * Returns data for an InputOption instance.
	 *
	 * @param InputOption $option Option to describe
	 * @return array
	 */
	private function getInputOptionData(InputOption $option)
	{
		return [
			'name' => '--' . $option->getName(),
			'shortcut' => $option->getShortcut() ? '-' . implode('|-', explode('|', $option->getShortcut())) : '',
			'accept_value' => $option->acceptValue(),
			'is_value_required' => $option->isValueRequired(),
			'is_multiple' => $option->isArray(),
			'description' => preg_replace('#\s*[\r\n]\s*#',' ', $option->getDescription()),
			'default' => $option->getDefault(),
		];
	}
	
	/**
	 * Returns data for an InputDefinition instance.
	 *
	 * @param InputDefinition $definition Definition to describe
	 * @return array
	 */
	private function getInputDefinitionData(InputDefinition $definition)
	{
		$arguments = [];
		foreach ($definition->getArguments() as $name => $argument) {
			if ( ! in_array($name, ['kernel', 'command'])) {
				$arguments[$name] = $this->getInputArgumentData($argument);
			}
		}
		
		$options = [];
		foreach ($definition->getOptions() as $name => $option) {
			$options[$name] = $this->getInputOptionData($option);
		}
		
		return [
			'arguments' => $arguments,
			'options' => $options,
		];
	}
	
	/**
	 * Returns data for a Command instance.
	 *
	 * @param Command $command Command to describe
	 * @return array
	 */
	private function getCommandData(Command $command, $addKernel = true)
	{
		$container = $command->getApplication()->getKernel()->getContainer();
		$global = in_array($command->getName(), $container->getParameter('motana.multikernel.commands.global'));
		
		$kernel = $command->getApplication() instanceof MultikernelApplication ? null : $command->getApplication()->getKernel()->getName();
		
		$command->getSynopsis();
		$command->mergeApplicationDefinition(false);
		
		$usages = [];
		if ( ! $kernel) {
			$usages[] = $this->makePathRelative($_SERVER['PHP_SELF']) . ' ' . str_replace([' <kernel>', ' <command>' ], '', $command->getSynopsis(true));
		}
		
		if ( ! $global) {
			foreach (array_merge([ $command->getSynopsis(true) ], $command->getUsages(), $command->getAliases()) as $usage) {
				$usages[] = $this->makePathRelative($_SERVER['PHP_SELF']) . ' ' . ($kernel ? $kernel : '<kernel>') . ' ' . str_replace([ ' <kernel>', ' <command>' ], '', $usage);
			}
		}
		
		$data = [
			'name' => $command->getName(),
			'usage' => $usages,
			'description' => $command->getDescription(),
			'help' => $this->getProcessedHelp($command),
			'kernels' => $command->getApplication() instanceof MultikernelApplication ? array_keys($command->getApplication()->getKernel()->getKernels()) : null,
			'definition' => $this->getInputDefinitionData($command->getDefinition()),
		];
		
		if ( ! $global || ! $command->getApplication() instanceof MultikernelApplication || ! $addKernel) {
			unset($data['kernels']);
		}
		
		return $data;
	}
	
	/**
	 * Output data in JSON format.
	 *
	 * @param array $data Data to output
	 * @param array $options Display options
	 */
	private function writeData(array $data, array $options = [])
	{
		$this->write(json_encode($data, JSON_PRETTY_PRINT) . "\n");
	}
}
