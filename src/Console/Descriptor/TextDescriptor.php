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
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Motana\Bundle\MultiKernelBundle\Console\MultiKernelApplication;

/**
 * A replacement for the Symfony Standard Edition text descriptor.
 * 
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class TextDescriptor extends Descriptor
{
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see Symfony\Component\Console\Descriptor\Descriptor::describeInputArgument()
	 */
	protected function describeInputArgument(InputArgument $argument, array $options = array())
	{
		if (null !== $argument->getDefault() && ( ! is_array($argument->getDefault()) || count($argument->getDefault()))) {
			$default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($argument->getDefault()));
		} else {
			$default = '';
		}
		
		$totalWidth = isset($options['total_width']) ? $options['total_width'] : Helper::strlen($argument->getName());
		$spacingWidth = $totalWidth - strlen($argument->getName());
		
		$this->writeText(sprintf('  <info>%s</info>  %s%s%s',
			$argument->getName(),
			str_repeat(' ', $spacingWidth),
			// + 4 = 2 spaces before <info>, 2 spaces after </info>
			preg_replace('/\s*[\r\n]\s*/', "\n".str_repeat(' ', $totalWidth + 4), $argument->getDescription()),
			$default
		), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputOption()
	 */
	protected function describeInputOption(InputOption $option, array $options = array())
	{
		if ($option->acceptValue() && null !== $option->getDefault() && ( ! is_array($option->getDefault()) || count($option->getDefault()))) {
			$default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($option->getDefault()));
		} else {
			$default = '';
		}
		
		$value = '';
		if ($option->acceptValue()) {
			$value = '=' . strtoupper($option->getName());
			if ($option->isValueOptional()) {
				$value = '[' . $value . ']';
			}
		}
		
		$totalWidth = isset($options['total_width']) ? $options['total_width'] : $this->calculateTotalWidthForOptions(array($option));
		$shortcutWidth = isset($options['shortcut_width']) ? $options['shortcut_width'] : $this->calculateTotalWidthForShortcuts(array($option));
		$shortcutWidth = $shortcutWidth > 0 ? $shortcutWidth - Helper::strlen($option->getShortcut()) - 1 : 0;
		
		$synopsis = sprintf('%s%s%s',
			$option->getShortcut() ? sprintf('-%s', $option->getShortcut()) : ' ',
			$shortcutWidth > 0 ? str_repeat(' ', $shortcutWidth) : '',
			sprintf('--%s%s', $option->getName(), $value)
		);
		
		$spacingWidth = $totalWidth - Helper::strlen($synopsis);
		
		$this->writeText(sprintf('  <info>%s</info>%s%s%s%s',
			$synopsis,
			str_repeat(' ', $spacingWidth),
			// + 17 = 2 spaces + <info> + </info> + 2 spaces
			preg_replace('/\s*[\r\n]\s*/', "\n" . str_repeat(' ', $totalWidth + 4), $option->getDescription()),
			$default,
			$option->isArray() ? '<comment> (multiple values allowed)</comment>' : ''
		), $options);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeInputDefinition()
	 */
	protected function describeInputDefinition(InputDefinition $definition, array $options = array())
	{
		$totalWidth = isset($options['total_width']) ? $options['total_width'] : $this->calculateTotalWidthForOptions($definition->getOptions());
		
		foreach ($definition->getArguments() as $argument) {
			$totalWidth = max($totalWidth, Helper::strlen($argument->getName()));
		}

		$shortcutWidth = $this->calculateTotalWidthForShortcuts($definition->getOptions());
		
		if ($definition->getArguments()) {
			$hasOwnArguments = 0;
			foreach ($definition->getArguments() as $argument) {
				if ('command' !== $argument->getName()) {
					++$hasOwnArguments;
				}
			}
			
			if ($hasOwnArguments) {
				$this->writeText('<comment>Arguments:</comment>', $options);
				$this->writeText("\n");
				
				$argWidth = $this->calculateTotalWidthForArguments($definition->getArguments());
				foreach ($definition->getArguments() as $name => $argument) {
					if ('command' !== $name) {
						$this->describeInputArgument($argument, array_merge($options, array(
							'total_width' => $argWidth
						)));
						$this->writeText("\n");
					}
				}
			}
		}
		
		if ($definition->getArguments() && $definition->getOptions()) {
			$this->writeText("\n");
		}
		
		if ($definition->getOptions()) {
			$this->writeText('<comment>Options:</comment>', $options);
			
			$laterOptions = array();
			foreach ($definition->getOptions() as $option) {
				if (strlen($option->getShortcut()) > 1) {
					$laterOptions[] = $option;
					continue;
				}
				
				$this->writeText("\n");
				$this->describeInputOption($option, array_merge($options, array(
					'total_width' => $totalWidth,
					'shortcut_width' => $shortcutWidth,
				)));
			}
			
			foreach ($laterOptions as $option) {
				$this->writeText("\n");
				$this->describeInputOption($option, array_merge($options, array(
					'total_width' => $totalWidth,
					'shortcut_width' => $shortcutWidth,
				)));
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeCommand()
	 */
	protected function describeCommand(Command $command, array $options = array())
	{
		$kernel = $command->getApplication() instanceof MultiKernelApplication ? null : $command->getApplication()->getKernel()->getName();
		
		$command->getSynopsis(true);
		$command->getSynopsis(false);
		$command->mergeApplicationDefinition(false);
		
		if ('' !== $help = $command->getApplication()->getHelp()) {
			$this->writeText($help."\n\n", $options);
		}
		
		$this->writeText('<comment>Usage:</comment>', $options);
		
		if ( ! $kernel) {
			$this->writeText("\n");
			$this->writeText('  '.$_SERVER['PHP_SELF'] . ' ' . str_replace(array(' <kernel>', ' <command>'), '', $command->getSynopsis(true)));
		}
		
		foreach (array_merge(array($command->getSynopsis(true)), $command->getAliases(), $command->getUsages()) as $usage) {
			$this->writeText("\n");
			$this->writeText('  '.$_SERVER['PHP_SELF'] . ' ' . ($kernel ? $kernel : '<kernel>') . ' ' . str_replace(array(' <kernel>', ' <command>'), '', $usage));
		}
		
		$this->writeText("\n");
		
		$definition = $command->getNativeDefinition();
		if ($definition->getOptions() || $definition->getArguments()) {
			$this->writeText("\n");
			$this->describeInputDefinition($definition, $options);
			$this->writeText("\n");
		}
		
		if ($help = $this->getProcessedHelp($command)) {
			$this->writeText("\n");
			$this->writeText('<comment>Help:</comment>', $options);
			$this->writeText("\n");
			$this->writeText('  '.str_replace("\n", "\n  ", $help), $options);
			$this->writeText("\n");
		}
		$this->writeText("\n");
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Descriptor\Descriptor::describeApplication()
	 */
	protected function describeApplication(Application $application, array $options = array())
	{
		$describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
		$description = new ApplicationDescription($application, $describedNamespace);
		
		/*
		if (isset($options['raw_text']) && $options['raw_text']) {
			$width = $this->getColumnWidth($description->getCommands());
			
			foreach ($description->getCommands() as $command) {
				$this->writeText(sprintf("%-{$width}s %s\n", $command->getName(), $command->getDescription()), $options);
			}
			
			$this->writeText("\n");
			
			return;
		}
		*/
		
		if ('' !== $help = $application->getHelp()) {
			$this->writeText($help."\n\n", $options);
		}
		
		$this->writeText("<comment>Usage:</comment>\n", $options);
		
		$kernel = $application instanceof MultiKernelApplication ? null : $application->getKernel()->getName();
		
		if ($application instanceof MultiKernelApplication) {
			$this->writeText("  ".$_SERVER['PHP_SELF']."\n", $options);
			$this->writeText("    <info>To display the list of kernels and commands available on all kernels</info>", $options);
			$this->writeText("\n\n");
		}
		
		$this->writeText("  ".$_SERVER['PHP_SELF']." ".($kernel ? $kernel : '<kernel>')."\n", $options);
		if ($kernel) {
			$this->writeText("    <info>To display the list of commands available on the <comment>{$kernel}</comment> kernel</info>", $options);
		} else {
			$this->writeText("    <info>To display the list of commands available on a kernel</info>", $options);
		}
		$this->writeText("\n\n");
		
		if ($application instanceof MultiKernelApplication) {
			$this->writeText("  ".$_SERVER['PHP_SELF']." <command> [options] [--] [arguments]\n", $options);
			$this->writeText("    <info>To run a command for all kernels</info>", $options);
			$this->writeText("\n\n");
		}
		
		$this->writeText("  ".$_SERVER['PHP_SELF']." ".($kernel ? $kernel : '<kernel>')." <command> [options] [--] [arguments]\n", $options);
		if ($kernel) {
			$this->writeText("    <info>To run a command on the <comment>{$kernel}</comment> kernel</info>", $options);
		} else {
			$this->writeText("    <info>To run a command on the on a kernel</info>", $options);
		}
		$this->writeText("\n\n");
	
		if ($application instanceof MultiKernelApplication) {
			$this->writeText("<comment>Kernels:</comment>\n");
			foreach ($application->getKernel()->getKernels() as $kernel) {
				$this->writeText('  '.$kernel->getName(), $options);
				$this->writeText("\n");
			}
			$this->writeText("\n");
		}

		$width = max($this->getColumnWidth($description->getCommands()), $this->calculateTotalWidthForOptions($application->getDefinition()->getOptions()));
		
		$this->describeInputDefinition(new InputDefinition($application->getDefinition()->getOptions()), array_merge($options, array(
			'total_width' => $width,
		)));
		$this->writeText("\n\n");
		

		if ($describedNamespace) {
			$this->writeText(sprintf('<comment>Commands in namespace "%s":</comment>', $describedNamespace), $options);
		} else {
			$this->writeText('<comment>Commands:</comment>', $options);
		}
		
		$commands = $description->getCommands();
		foreach ($description->getNamespaces() as $namespace) {
			if ( ! $describedNamespace && ApplicationDescription::GLOBAL_NAMESPACE !== $namespace['id']) {
				$this->writeText("\n\n");
				$this->writeText(' <comment>'.$namespace['id'].'</comment>', $options);
			}
			
			foreach ($namespace['commands'] as $name) {
				if (isset($commands[$name])) {
					$this->writeText("\n");
					$spacingWidth = $width - Helper::strlen($name);
					$command = $commands[$name];
					$commandAliases = $this->getCommandAliasesText($command);
					$this->writeText(sprintf('  <info>%s</info>%s%s', $name, str_repeat(' ', $spacingWidth), $commandAliases.$command->getDescription()), $options);
				}
			}
		}
		
		$this->writeText("\n\n");
	}
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Writes content to output.
	 * 
	 * @param string $content Content to write
	 * @param array $options Display options
	 */
	private function writeText($content, array $options = array())
	{
		$this->write(
			isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
			isset($options['raw_output']) ? ! $options['raw_output'] : true
		);
	}
	
	/**
	 * Formats command aliases to show them in the command description.
	 *
	 * @param Command $command
	 * @return string
	 */
	private function getCommandAliasesText(Command $command)
	{
		$text = '';
		$aliases = $command->getAliases();
		
		if ($aliases) {
			$text = '['.implode('|', $aliases).'] ';
		}
		
		return $text;
	}
	
	/**
	 * Formats input option/argument default value.
	 *
	 * @param mixed $default
	 * @return string
	 */
	private function formatDefaultValue($default)
	{
		if (is_string($default)) {
			$default = OutputFormatter::escape($default);
		} elseif (is_array($default)) {
			foreach ($default as $key => $value) {
				if (is_string($value)) {
					$default[$key] = OutputFormatter::escape($value);
				}
			}
		}
		
		return str_replace('\\\\', '\\', json_encode($default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
	
	/**
	 * Returns the column width for command names.
	 * 
	 * @param Command[] $commands An array of commands to process
	 * @return int
	 */
	private function getColumnWidth(array $commands)
	{
		$widths = array();
		
		foreach ($commands as $command) {
			$widths[] = Helper::strlen($command->getName());
			foreach ($command->getAliases() as $alias) {
				$widths[] = Helper::strlen($alias);
			}
		}
		
		return max($widths) + 2;
	}
	
	/**
	 * Calculate the total width for the arguments in the arguments list.
	 * 
	 * @param InputArgument[] $arguments An array of arguments to process
	 * @return int
	 */
	private function calculateTotalWidthForArguments(array $arguments)
	{
		$widths = array();
		
		foreach ($arguments as $argument) {
			$widths[] = Helper::strlen($argument->getName());
		}
		
		return max($widths);
	}
	
	/**
	 * Calculate the total width for the shortcuts column in the option list.
	 *
	 * @param InputOption[] $commands Array of options
	 * @return integer
	 */
	private function calculateTotalWidthForShortcuts(array $options)
	{
		// Determine the maximum width of all shortcut names
		$totalWidth = 0;
		foreach ($options as $option) {
			// "-" + shortcut
			$totalWidth = max($totalWidth, 1 + Helper::strlen($option->getShortcut()));
		}
		
		// Return the maximum width + 2
		return $totalWidth + 2;
	}
	
	/**
	 * Calculates the total width for options.
	 *
	 * @param InputOption[] $options An array of options to process
	 * @return int
	 */
	private function calculateTotalWidthForOptions(array $options)
	{
		$totalWidth = 0;
		$shortcutWidth = $this->calculateTotalWidthForShortcuts($options);
		foreach ($options as $option) {
			// "-" + shortcut + ", --" + name
			$nameLength = $shortcutWidth + 4 + Helper::strlen($option->getName());
			
			if ($option->acceptValue()) {
				$valueLength = 1 + Helper::strlen($option->getName()); // = + value
				$valueLength += $option->isValueOptional() ? 2 : 0; // [ + ]
				
				$nameLength += $valueLength;
			}
			$totalWidth = max($totalWidth, $nameLength);
		}
		
		return $totalWidth;
	}
	
	// }}}
}
