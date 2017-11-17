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
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * A replacement for the Symfony Standard Edition markdown descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class MarkdownDescriptor extends Descriptor
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputArgument()
	 */
	protected function describeInputArgument(InputArgument $argument, array $options = [])
	{
		$this->write([
			'**' . $argument->getName() . ':**' . "\n\n",
			'* Name: '. ($argument->getName() ?: '<none>') . "\n",
			'* Is required: ' . ($argument->isRequired() ? 'yes' : 'no') . "\n",
			'* Is array: '. ($argument->isArray() ? 'yes' : 'no') . "\n",
			'* Description: ' . preg_replace('#\s*[\r\n]\s*#', "\n  ", $this->formatDescription($argument->getDescription()) ?: '<none>') . "\n",
			'* Default: `' . str_replace("\n", '', var_export($argument->getDefault(), true)) . '`',
		]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputOption()
	 */
	protected function describeInputOption(InputOption $option, array $options = [])
	{
		$this->write([
			'**' . $option->getName() . ':**' . "\n\n",
			'* Name: `--'. $option->getName() . '`' . "\n",
			'* Shortcut: ' . ($option->getShortcut() ? '`-' . implode('|-', explode('|', $option->getShortcut())) . '`' : '<none>') . "\n",
			'* Accepts value: ' . ($option->acceptValue() ? 'yes' : 'no') . "\n",
			'* Is value required: ' . ($option->isValueRequired() ? 'yes' : 'no') . "\n",
			'* Is multiple: ' . ($option->isArray() ? 'yes' : 'no') . "\n",
			'* Description: ' . preg_replace('#\s*[\r\n]\s*#', "\n  ", $this->formatDescription($option->getDescription()) ?: '<none>') . "\n",
			'* Default: `' . str_replace("\n", '', var_export($option->getDefault(), true)) . '`',
		]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputDefinition()
	 */
	protected function describeInputDefinition(InputDefinition $definition, array $options = [])
	{
		if ($showArguments = count($definition->getArguments()) > 0) {
			$this->write('### Arguments:');
			
			foreach ($definition->getArguments() as $name => $argument) {
				if ( ! in_array($name, [ 'kernel', 'command' ])) {
					$this->write("\n\n");
					$this->describeInputArgument($argument, $options);
				}
			}
		}
		
		if (count($definition->getOptions()) > 0) {
			if ($showArguments) {
				$this->write("\n\n");
			}
			
			$this->write('### Options:');
			foreach ($definition->getOptions() as $option) {
				$this->write("\n\n");
				$this->describeInputOption($option, $options);
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeCommand()
	 */
	protected function describeCommand(Command $command, array $options = [])
	{
		$container = $command->getApplication()->getKernel()->getContainer();
		$global = in_array($command->getName(), $container->getParameter('motana.multikernel.commands.global'));
		
		$kernel = $command->getApplication() instanceof MultikernelApplication ? null : $command->getApplication()->getKernel()->getName();
		
		$command->getSynopsis();
		$command->mergeApplicationDefinition(false);
		
		$usages = [];
		if ( ! $kernel) {
			$usages[] = str_replace([ ' <kernel>', ' <command>' ], '', $command->getSynopsis(true));
		}
		
		if ( ! $global) {
			foreach (array_merge([ $command->getSynopsis(true) ], $command->getUsages(), $command->getAliases()) as $usage) {
				$usages[] = ($kernel ? $kernel : '<kernel>') . ' ' . str_replace([ ' <kernel>', ' <command>' ], '', $usage);
			}
		}
		
		$descriptor = $this;
		$this->write([
			'Command "' . $command->getName() . '"' . "\n",
			str_repeat('-', strlen($command->getName()) + 10) . "\n\n",
			'* Description: ' . ($this->formatDescription($command->getDescription()) ?: '<none>') . "\n",
			'* Usage:' . "\n\n",
			array_reduce($usages, function($carry, $usage) use ($descriptor) {
				return $carry .= '  * `' . $descriptor->makePathRelative($_SERVER['PHP_SELF']) . ' ' . $usage . '`' . "\n";
			})
		]);
		
		if ($help = $this->getProcessedHelp($command)) {
			$this->write("\n");
			$this->write($this->formatDescription($help));
			$this->write("\n");
		}

		$application = $command->getApplication();
		if ( ! $global && $application instanceof MultikernelApplication) {
			$this->write("\n\n");
			$this->write([
				'### Kernels:' . "\n",
				array_reduce(array_keys($application->getKernel()->getKernels()), function($carry, $kernel) {
					return $carry .= "\n" . '* ' . $kernel;
				}),
				"\n",
			]);
		}
		
		if ($command->getDefinition()) {
			$this->write("\n");
			$this->describeInputDefinition($command->getDefinition(), $options);
		}
		
		$this->write("\n");
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeApplication()
	 */
	protected function describeApplication(Application $application, array $options = [])
	{
		$describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
		$description = new ApplicationDescription($application, $describedNamespace);
		
		if ('' !== $help = $this->formatDescription($application->getHelp())) {
			$this->write($help. "\n" . str_repeat('=', Helper::strlen($help)));
		}
		
		if ($application instanceof MultikernelApplication) {
			$this->write("\n\n");
			$this->write([
				'Kernels' . "\n",
				'-------' . "\n",
				array_reduce(array_keys($application->getKernel()->getKernels()), function($carry, $kernel) {
					return $carry .= "\n" . '* ' . $kernel;
				}),
			]);
		}
		
		$this->write("\n\n");
		if ($describedNamespace) {
			$this->write([
				'Commands in namespace "'. $describedNamespace . '"' . "\n",
				'------------------------' . str_repeat('-', Helper::strlen($describedNamespace)),
			]);
		} else {
			$this->write([
				'Commands' . "\n",
				'--------',
			]);
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
		
		foreach ($namespaces as $namespace) {
			if (ApplicationDescription::GLOBAL_NAMESPACE !== $namespace['id']) {
				$this->write("\n\n");
				$this->write('**' . $namespace['id'] . ':**');
			}
			
			$this->write("\n\n");
			$this->write(implode("\n", array_map(function($commandName) {
				return '* ' . $commandName;
			}, $namespace['commands'])));
		}
		
		$this->write("\n");
		foreach ($description->getCommands() as $command) {
			$this->write("\n");
			$this->describeCommand($command, $options);
		}
	}
	
	/**
	 * Format the description or processed help of a command for display in Markdown output.
	 *
	 * @param string $text Text to process
	 * @return string
	 */
	private function formatDescription($text)
	{
		$text = str_replace([ '<info>', '</info>' ], '`', $text);
		
		return strip_tags($text);
	}
}
