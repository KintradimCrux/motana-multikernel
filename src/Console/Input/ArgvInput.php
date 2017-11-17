<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Input;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * A more advanced version of the Symfony ArgvInput that is able to handle
 * {@link ConditionalArgument}s.
 *
 * This class would extend the original ArgvInput class, if it was possible to
 * override the parseArgument() method. I used a copy since the declaration is 'private'.
 *
 * The parseArgument() method has been modified to handle a {@link ConditionalArgument} correctly.
 *
 * The methods hasParameterOption() and getParameterOption() have been changed to handle the -eprod option notation correctly.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ArgvInput extends Input
{
	/**
	 * Tokens to parse.
	 *
	 * @var array
	 */
	private $tokens = [];
	
	/**
	 * Stack for tokens left to parse.
	 *
	 * @var array
	 */
	private $parsed = [];
	
	/**
	 * Constructor.
	 *
	 * @param array $argv Array of parameters from the CLI (in $_SERVER['argv'] format)
	 * @param InputDefinition $definition Input definition
	 */
	public function __construct(array $argv = null, InputDefinition $definition = null)
	{
		// Use the global argv array if none specified
		if (null === $argv) {
			$argv = $_SERVER['argv'];
		}
		
		// Strip the application name
		if ($_SERVER['PHP_SELF'] === $argv[0]) {
			array_shift($argv);
		}
		
		$this->tokens = $argv;
		
		parent::__construct($definition);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\Input::parse()
	 */
	protected function parse()
	{
		// Enable parsing options by default
		$parseOptions = true;

		// Reset tokens to parse
		$this->parsed = $this->tokens;
		
		// Parse all tokens
		while (null !== $token = array_shift($this->parsed)) {
			if ($parseOptions && '' == $token) {
				$this->parseArgument($token);
			} elseif ($parseOptions && $token == '--') {
				$parseOptions = false;
			} elseif ($parseOptions && 0 === strpos($token, '--')) {
				$this->parseLongOption($token);
			} elseif ($parseOptions && '-' === $token[0] && '-' !== $token) {
				$this->parseShortOption($token);
			} else {
				$this->parseArgument($token);
			}
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\InputInterface::getFirstArgument()
	 */
	public function getFirstArgument()
	{
		// Process all tokens
		foreach ($this->tokens as $token)
		{
			// Skip option tokens
			if ($token && '-' === $token[0]) {
				continue;
			}
			
			// Return the first non-option token
			return $token;
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\InputInterface::hasParameterOption()
	 */
	public function hasParameterOption($values, $onlyParams = false)
	{
		// Convert values to array
		$values = (array) $values;
		
		// Process all tokens
		foreach ($this->tokens as $token)
		{
			// Break loop when reached the end of arguments marker and not searching for parameters
			if ($onlyParams && '--' === $token) {
				return false;
			}
			
			// Compare the token with all specified values
			foreach ($values as $value)
			{
				// Skip empty values
				if ('' == $value) {
					continue;
				}
				
				// If the token equals the value or starts with the value, return TRUE to signal existence
				if ($token === $value) {
					return true;
				} elseif (0 === strpos($token, $value)) {
					return true;
				}
			}
		}
		
		// Return FALSE to signal non-existence
		return false;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\InputInterface::getParameterOption()
	 */
	public function getParameterOption($values, $default = false, $onlyParams = false)
	{
		// Convert values to array
		$values = (array) $values;
		$tokens = $this->tokens;
		
		// Process all tokens
		while (0 < count($tokens))
		{
			// Get next token
			$token = array_shift($tokens);
			
			// Break loop when reached the end of arguments marker and not searching for parameters
			if ($onlyParams && '--' === $token) {
				return false;
			}
			
			// Compare the token with all specified values
			foreach ($values as $value)
			{
				// Skip empty values
				if ('' == $value) {
					continue;
				}
				
				// Token equals the value, look for parameter value in current or next token
				if ($token === $value)
				{
					// Get next token
					$next = current($tokens);
					
					// Token is a parameter
					if ('-' === $token[0])
					{
						// Next token contains a dash only, return next token
						if ('-' === $next || '-' !== $next[0]) {
							return array_shift($tokens);
						}
						
						// Next token contains something else, return the default value
						else {
							return $default;
						}
					}
					
					// Return the token
					return $token;
				}
				
				// Token starts with the value; if the token contains a parameter value, return it
				elseif (0 === strpos($token, $value)) {
					if (false !== $pos = strpos($token, '=')) {
						return substr($token, $pos + 1);
					} else {
						return substr($token, strlen($value));
					}
				}
			}
		}
		
		// Return the default value
		return $default;
	}
	
	/**
	 * Remove the first argument token.
	 */
	public function shift()
	{
		// Process all tokens
		foreach ($this->tokens as $index => $token)
		{
			// Skip option parameters
			if ($token && '-' === $token[0]) {
				continue;
			}
			
			// Remove the first non-option parameter
			array_splice($this->tokens, $index, 1, []);
			
			// Break the loop
			break;
		}
	}
	
	/**
	 * Returns the string representation of the parsed commandline.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$tokens = [];
		
		foreach ($this->tokens as $token) {
			if (preg_match('{^(-[^=]+=)(.+)}', $token, $match)) {
				$tokens[] = $match[1].$this->escapeToken($match[2]);
			} elseif ($token && $token[0] !== '-') {
				$tokens[] = $this->escapeToken($token);
			} else {
				$tokens[] = $token;
			}
		}
		
		return implode(' ', $tokens);
	}
	
	/**
	 * Add a long option.
	 *
	 * @param string $name Option name
	 * @param string $value Option value
	 * @throws \InvalidArgumentException
	 */
	private function addLongOption($name, $value)
	{
		// Check the specified option exists
		if ( ! $this->definition->hasOption($name)) {
			throw new \RuntimeException(sprintf('The "--%s" option does not exist.', $name));
		}
		
		// Get the option definition
		$option = $this->definition->getOption($name);
		
		// Convert empty values to NULL
		if ( ! isset($value[0])) {
			$value = null;
		}
		
		// Value is not NULL, check the option accepts a value
		if (null !== $value && ! $option->acceptValue()) {
			throw new \RuntimeException(sprintf('The "--%s" option does not accept a value.', $name));
		}
		
		// Value is NULL, the option accepts value and there are tokens to parse, try to get the value from next token
		if (null === $value && $option->acceptValue() && count($this->parsed)) {
			$next = array_shift($this->parsed);
			if (isset($next[0]) && ('-' === $next || '-' !== $next[0])) {
				$value = $next;
			} elseif (empty($next)) {
				$value = null;
			} else {
				array_unshift($this->parsed, $next);
			}
		}
		
		// Value is NULL
		if (null === $value)
		{
			// Check the option requires a value
			if ($option->isValueRequired()) {
				throw new \RuntimeException(sprintf('The "--%s" option requires a value.', $name));
			}
			
			// Use default value for non-array options that are optional
			if ( ! $option->isArray()) {
				$value = $option->isValueOptional() ? $option->getDefault() : true;
			}
		}
		
		// Add the option
		if ($option->isArray()) {
			if (null !== $value) {
				$this->options[$name][] = $value;
			}
		} else {
			$this->options[$name] = $value;
		}
	}
	
	/**
	 * Add a short option.
	 *
	 * @param string $shortcut Option shortcut
	 * @param string  $value Option value
	 * @throws \RuntimeException
	 */
	private function addShortOption($shortcut, $value)
	{
		// Check the specified option shortcut exists
		if ( ! $this->definition->hasShortcut($shortcut)) {
			throw new \RuntimeException(sprintf('The "-%s" option does not exist.', $shortcut));
		}
		
		// Add the option
		$this->addLongOption($this->definition->getOptionForShortcut($shortcut)->getName(), $value);
	}
	
	/**
	 * Parse an argument.
	 *
	 * @param string $token The current token
	 * @throws \RuntimeException
	 */
	private function parseArgument($token)
	{
		// Get the number of already processed arguments
		$c = count($this->arguments);
		
		// Argument available
		if ($this->definition->hasArgument($c))
		{
			// Get the argument definition
			$arg = $this->definition->getArgument($c);
			
			// Add regular arguments, or conditional arguments if the condition evaluates to true
			if ($arg instanceof ConditionalArgument ? $arg->getResult($token) : true) {
				$this->arguments[$arg->getName()] = $arg->isArray() ? [ $token ] : $token;
			}
			
			// Condition of a conditional argument evaluated to false, add the default value and parse the token again
			else {
				$this->arguments[$arg->getName()] = $arg->getDefault();
				array_unshift($this->parsed, $token);
			}
			
			return;
		}
		
		// Previous argument is an array argument, add the token to its values
		if ($this->definition->hasArgument($c - 1) && $this->definition->getArgument($c - 1)->isArray()) {
			$arg = $this->definition->getArgument($c - 1);
			$this->arguments[$arg->getName()][] = $token;
			return;
		}
		
		// Get arguments from definition
		$all = $this->definition->getArguments();
		
		// Too many arguments were specified, throw an exception
		if (count($all)) {
			throw new \RuntimeException(sprintf('Too many arguments, expected arguments: "%s".', implode('" "', array_keys($all))));
		}
		
		// No arguments accepted at all, throw an exception
		throw new \RuntimeException(sprintf('No arguments expected, got "%s".', $token));
	}
	
	/**
	 * Parse a long option.
	 *
	 * @param string $token The current token
	 */
	private function parseLongOption($token)
	{
		// Strip the option prefix
		$name = substr($token, 2);
		
		// Token contains a value
		if (false !== $pos = strpos($name, '='))
		{
			// No option value found in token, add a NULL token to parse
			if (0 === strlen($value = substr($name, $pos + 1))) {
				array_unshift($this->parsed, null);
			}
			
			// Add the option
			$this->addLongOption(substr($name, 0, $pos), $value);
		}
		
		// Token contains no value, add the option
		else {
			$this->addLongOption($name, null);
		}
	}
	
	/**
	 * Parse a short option.
	 *
	 * @param string $token The current token
	 */
	private function parseShortOption($token)
	{
		// Strip the shortcut prefix
		$name = substr($token, 1);
		
		// Token is longer than 1 character
		if (strlen($name) > 1)
		{
			// Option accepts a value, add option with value
			if ($this->definition->hasShortcut($name[0]) && $this->definition->getOptionForShortcut($name[0])->acceptValue()) {
				$this->addShortOption($name[0], substr($name, '=' === $name[1] ? 2 : 1));
			}
			
			// Option accepts no value, parse short option set
			else {
				$this->parseShortOptionSet($name);
			}
		}
		
		// Token is only 1 character
		else
		{
			// Get next token
			$next = current($this->parsed);
			
			// Next token contains a dash, add option with value
			if ('-' === $next || '-' !== $next[0]) {
				$this->addShortOption($name, array_shift($this->parsed));
			}
			
			// Add option without value
			else {
				$this->addShortOption($name, null);
			}
		}
	}
	
	/**
	 * Parse a short option set.
	 *
	 * @param string $token The current token
	 * @throws \RuntimeException
	 */
	private function parseShortOptionSet($token)
	{
		// Get token length
		$len = strlen($token);
		
		// Process each character of the token
		for ($i = 0; $i < $len; ++$i)
		{
			// Current character is not a valid shortcut
			if ( ! $this->definition->hasShortcut($token[$i]))
			{
				// Previous option does not accept a value, throw an exception
				if ('=' === $token[$i] && isset($token[$i - 1])) {
					throw new \RuntimeException(sprintf('The "-%s" option accepts no value.', $token[$i - 1]));
				}
				
				// Option does not exist, throw an exception
				else {
					throw new \RuntimeException(sprintf('The "-%s" option does not exist.', $token[$i]));
				}
			}
			
			// Get the option definition
			$option = $this->definition->getOptionForShortcut($token[$i]);
			
			// Option accepts value, add option with value and break the loop
			if ($option->acceptValue()) {
				$this->addLongOption($option->getName(), $i == $len - 1 ? null : substr($token, $i + 1));
				
				break;
			}
			
			// Option accepts no value, add option without value
			else {
				$this->addLongOption($option->getName(), null);
			}
		}
	}
}
