<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Console\Descriptor;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;

/**
 * A replacement for the Symfony Standard Edition xml descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class XmlDescriptor extends Descriptor
{
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputArgument()
	 */
	protected function describeInputArgument(InputArgument $argument, array $options = array())
	{
		$this->writeDocument($this->getInputArgumentDocument($argument), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputOption()
	 */
	protected function describeInputOption(InputOption $option, array $options = array())
	{
		$this->writeDocument($this->getInputOptionDocument($option), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputDefinition()
	 */
	protected function describeInputDefinition(InputDefinition $definition, array $options = array())
	{
		$this->writeDocument($this->getInputDefinitionDocument($definition), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeCommand()
	 */
	protected function describeCommand(Command $command, array $options = array())
	{
		$this->writeDocument($this->getCommandDocument($command), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeApplication()
	 */
	protected function describeApplication(Application $application, array $options = array())
	{
		$this->writeDocument($this->getApplicationDocument($application, isset($options['namespace']) ? $options['namespace'] : null), $options);
	}
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Returns the DOM document for an InputArgument instance.
	 *
	 * @param InputArgument $argument Argument to describe
	 * @return \DOMDocument
	 */
	private function getInputArgumentDocument(InputArgument $argument)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->appendChild($objectXML = $dom->createElement('argument'));
		$objectXML->setAttribute('name', $argument->getName());
		$objectXML->setAttribute('is_required', $argument->isRequired() ? 1 : 0);
		$objectXML->setAttribute('is_array', $argument->isArray() ? 1 : 0);
		
		$objectXML->appendChild($descriptionXML = $dom->createElement('description'));
		$descriptionXML->appendChild($dom->createTextNode($argument->getDescription()));
		
		$objectXML->appendChild($defaultsXML = $dom->createElement('defaults'));
		$defaults = is_array($argument->getDefault()) ? $argument->getDefault() : (is_bool($argument->getDefault()) ? array(var_export($argument->getDefault(), true)) : ($argument->getDefault() ? array($argument->getDefault()) : array()));
		foreach ($defaults as $default) {
			$defaultsXML->appendChild($defaultXML = $dom->createElement('default'));
			$defaultXML->appendChild($dom->createTextNode($default));
		}
		
		return $dom;
	}
	
	/**
	 * Returns the DOM document for an InputOption instance.
	 *
	 * @param InputOption $option Option to describe
	 * @return \DOMDocument
	 */
	private function getInputOptionDocument(InputOption $option)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->appendChild($objectXML = $dom->createElement('option'));
		$objectXML->setAttribute('name', '--' . $option->getName());
		
		$pos = strpos($option->getShortcut(), '|');
		if (false !== $pos) {
			$objectXML->setAttribute('shortcut', '-'.substr($option->getShortcut(), 0, $pos));
			$objectXML->setAttribute('shortcuts', '-'.implode('|-', explode('|', $option->getShortcut())));
		} else {
			$objectXML->setAttribute('shortcut', $option->getShortcut() ? '-'.$option->getShortcut() : '');
		}
		
		$objectXML->setAttribute('accept_value', $option->acceptValue() ? 1 : 0);
		$objectXML->setAttribute('is_value_required', $option->isValueRequired() ? 1 : 0);
		$objectXML->setAttribute('is_multiple', $option->isArray() ? 1 : 0);
		
		$objectXML->appendChild($descriptionXML = $dom->createElement('description'));
		$descriptionXML->appendChild($dom->createTextNode($option->getDescription()));
		
		if ($option->acceptValue()) {
			$defaults = is_array($option->getDefault()) ? $option->getDefault() : (is_bool($option->getDefault()) ? array(var_export($option->getDefault(), true)) : ($option->getDefault() ? array($option->getDefault()) : array()));
			$objectXML->appendChild($defaultsXML = $dom->createElement('defaults'));
			if ( ! empty($defaults)) {
				foreach ($defaults as $default) {
					$defaultsXML->appendChild($defaultXML = $dom->createElement('default'));
					$defaultXML->appendChild($dom->createTextNode($default));
				}
			}
		}
		
		return $dom;
	}
	
	/**
	 * Returns the DOM document for an InputDefinition instance.
	 *
	 * @param InputDefinition $definition Definition to describe
	 * @return \DOMDocument
	 */
	private function getInputDefinitionDocument(InputDefinition $definition)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->appendChild($definitionXML = $dom->createElement('definition'));
		
		$definitionXML->appendChild($argumentsXML = $dom->createElement('arguments'));
		foreach ($definition->getArguments() as $name => $argument) {
			if ( ! in_array($name, ['kernel', 'command'])) {
				$this->appendDocument($argumentsXML, $this->getInputArgumentDocument($argument));
			}
		}
		
		$definitionXML->appendChild($optionsXML = $dom->createElement('options'));
		foreach ($definition->getOptions() as $option) {
			$this->appendDocument($optionsXML, $this->getInputOptionDocument($option));
		}
		
		return $dom;
	}
	
	/**
	 * Returns the DOM document for a Command instance.
	 *
	 * @param Command $command Command to describe
	 * @param boolean $addKernels Boolean indicating to add kernels
	 * @return \DOMDocument
	 */
	private function getCommandDocument(Command $command, $addKernels = true)
	{
		$container = $command->getApplication()->getKernel()->getContainer();
		$global = in_array($command->getName(), $container->getParameter('motana.multikernel.commands.global'));
		
		$kernel = $command->getApplication() instanceof MultikernelApplication ? null : $command->getApplication()->getKernel()->getName();
		
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->appendChild($commandXML = $dom->createElement('command'));
		
		$command->getSynopsis();
		$command->mergeApplicationDefinition(false);
		
		$commandXML->setAttribute('id', $command->getName());
		$commandXML->setAttribute('name', $command->getName());
		
		$commandXML->appendChild($usagesXML = $dom->createElement('usages'));
		
		if ( ! $kernel) {
			$usagesXML->appendChild($dom->createElement('usage', $_SERVER['PHP_SELF'] . ' ' . str_replace(array(' <kernel>', ' <command>'), '', $command->getSynopsis(true))));
		}
		
		if ( ! $global) {
			foreach (array_merge(array($command->getSynopsis(true)), $command->getAliases(), $command->getUsages()) as $usage) {
				$usagesXML->appendChild($dom->createElement('usage', $_SERVER['PHP_SELF'] . ' ' . ($kernel ? $kernel : '<kernel>') . ' ' . str_replace(array(' <kernel>', ' <command>'), '', $usage)));
			}
		}
		
		$commandXML->appendChild($descriptionXML = $dom->createElement('description'));
		$descriptionXML->appendChild($dom->createTextNode(str_replace("\n", "\n ", $command->getDescription())));
		
		$commandXML->appendChild($helpXML = $dom->createElement('help'));
		$helpXML->appendChild($dom->createTextNode(str_replace("\n", "\n ", $this->getProcessedHelp($command))));
		
		if ( ! $global && ! $kernel && $addKernels) {
			$commandXML->appendChild($kernelsXML = $dom->createElement('kernels'));
			foreach ($command->getApplication()->getKernel()->getKernels() as $kernelName => $kernel) {
				$kernelsXML->appendChild($dom->createElement('kernel', $kernelName));
			}
		}
		
		$definitionXML = $this->getInputDefinitionDocument($command->getNativeDefinition());
		$this->appendDocument($commandXML, $definitionXML->getElementsByTagName('definition')->item(0));
		
		return $dom;
	}
	
	/**
	 * Returns the DOM document for an Application instance.
	 *
	 * @param Application $application Application to describe
	 * @param string $namespace Command namespace to show
	 * @return \DOMDocument
	 */
	private function getApplicationDocument(Application $application, $namespace = null)
	{
		$kernel = $application instanceof MultikernelApplication ? null : $application->getKernel()->getName();
		
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
		$dom->appendChild($rootXml = $dom->createElement('symfony'));
		
		if ($application->getName() !== 'UNKNOWN') {
			$rootXml->setAttribute('name', $application->getName());
			if ($application->getVersion() !== 'UNKNOWN') {
				$rootXml->setAttribute('version', $application->getVersion());
			}
			$rootXml->setAttribute('kernel', $application->getKernel()->getName());
		}
		
		if ( ! $kernel) {
			$rootXml->appendChild($kernelsXML = $dom->createElement('kernels'));
			foreach ($application->getKernel()->getKernels() as $kernelName => $kernel) {
				$kernelsXML->appendChild($dom->createElement('kernel', $kernelName));
			}
		}
		
		$rootXml->appendChild($commandsXML = $dom->createElement('commands'));
		
		$description = new ApplicationDescription($application, $namespace);
		
		if ($namespace) {
			$commandsXML->setAttribute('namespace', $namespace);
		}
		
		foreach ($description->getCommands() as $command) {
			$this->appendDocument($commandsXML, $this->getCommandDocument($command, false));
		}
		
		if ( ! $namespace) {
			$rootXml->appendChild($namespacesXML = $dom->createElement('namespaces'));
			
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
			
			foreach ($namespaces as $namespace) {
				$namespacesXML->appendChild($namespaceArrayXML = $dom->createElement('namespace'));
				$namespaceArrayXML->setAttribute('id', $namespace['id']);
				
				foreach ($namespace['commands'] as $name) {
					$namespaceArrayXML->appendChild($commandXML = $dom->createElement('command'));
					$commandXML->appendChild($dom->createTextNode($name));
				}
			}
		}
		
		return $dom;
	}

	/**
	 * Append document children to a parent node.
	 *
	 * @param \DOMNode $parentNode Parent node
	 * @param \DOMNode $importedParent Node to import
	 */
	private function appendDocument(\DOMNode $parentNode, \DOMNode $importedParent)
	{
		foreach ($importedParent->childNodes as $childNode) {
			$parentNode->appendChild($parentNode->ownerDocument->importNode($childNode, true));
		}
	}
	
	/**
	 * Writes a DOM document.
	 *
	 * @param \DOMDocument $dom
	 */
	private function writeDocument(\DOMDocument $dom, array $options = array())
	{
		$dom->formatOutput = true;
		
		$this->write($dom->saveXML());
	}
	
	// }}}
}
