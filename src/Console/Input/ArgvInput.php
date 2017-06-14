<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Console\Input;

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
	// {{{ Properties
	
	/**
	 * Tokens to parse.
	 * 
	 * @var array
	 */
	private $tokens = array();
	
	/**
	 * Stack for tokens left to parse.
	 * 
	 * @var array
	 */
	private $parsed = array();
	
	// }}}
	// {{{ Constructor
	
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
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\Input::parse()
	 */
	protected function parse()
	{
		$parseOptions = true;
		$this->parsed = $this->tokens;
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
		foreach ($this->tokens as $token) {
			if ($token && '-' === $token[0]) {
				continue;
			}
			
			return $token;
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\InputInterface::hasParameterOption()
	 */
	public function hasParameterOption($values, $onlyParams = false)
	{
		$values = (array) $values;
		
		foreach ($this->tokens as $token) {
			if ($onlyParams && '--' === $token) {
				return false;
			}
			
			foreach ($values as $value) {
				if ('' == $value) {
					continue;
				}
				
				if ($token === $value) {
					return true;
				} elseif (0 === strpos($token, $value)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Input\InputInterface::getParameterOption()
	 */
	public function getParameterOption($values, $default = false, $onlyParams = false)
	{
		$values = (array) $values;
		$tokens = $this->tokens;
		
		while (0 < count($tokens)) {
			$token = array_shift($tokens);
			if ($onlyParams && '--' === $token) {
				return false;
			}
			
			foreach ($values as $value) {
				if ('' == $value) {
					continue;
				}
				
				if ($token === $value) {
					$next = current($tokens);
					if ('-' === $token[0]) {
						if ('-' === $next || '-' !== $next[0]) {
							return array_shift($tokens);
						} else {
							return $default;
						}
					} 
					
					return $token;
				} elseif (0 === strpos($token, $value)) {
					if (false !== $pos = strpos($token, '=')) {
						return substr($token, $pos + 1);
					} else {
						return substr($token, strlen($value));
					}
				}
			}
		}
		
		return $default;
	}
	
	// }}}
	// {{{ Public methods
	
	/**
	 * Remove the first argument token.
	 */
	public function shift()
	{
		foreach ($this->tokens as $index => $token) {
			if ($token && '-' === $token[0]) {
				continue;
			}
			
			array_splice($this->tokens, $index, 1, array());
			
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
		$tokens = array();
		
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
	
	// }}}
	// {{{ Helper methods
	
	/**
	 * Add a long option.
	 *
	 * @param string $name Option name
	 * @param string $value Option value
	 * @throws \InvalidArgumentException
	 */
	private function addLongOption($name, $value) {
		if ( ! $this->definition->hasOption($name)) {
			throw new \RuntimeException(sprintf('The "--%s" option does not exist.', $name));
		}
		
		$option = $this->definition->getOption($name);
		
		if ( ! isset($value[0])) {
			$value = null;
		}
		
		if (null !== $value && ! $option->acceptValue()) {
			throw new \RuntimeException(sprintf('The "--%s" option does not accept a value.', $name));
		}
		
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
		
		if (null === $value) {
			if ($option->isValueRequired()) {
				throw new \RuntimeException(sprintf('The "--%s" option requires a value.', $name));
			}
			
			if ( ! $option->isArray()) {
				$value = $option->isValueOptional() ? $option->getDefault() : true;
			}
		}
		
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
	private function addShortOption($shortcut, $value) {
		if ( ! $this->definition->hasShortcut($shortcut)) {
			throw new \RuntimeException(sprintf('The "-%s" option does not exist.', $shortcut));
		}
		
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
		$c = count($this->arguments);
		
		if ($this->definition->hasArgument($c)) {
			$arg = $this->definition->getArgument($c);
			if ($arg instanceof ConditionalArgument ? $arg->getResult($token) : true) {
				$this->arguments[$arg->getName()] = $arg->isArray() ? array($token) : $token;
			} else {
				$this->arguments[$arg->getName()] = $arg->getDefault();
				array_unshift($this->parsed, $token);
			}
		} elseif ($this->definition->hasArgument($c - 1) && $this->definition->getArgument($c - 1)->isArray()) {
			$arg = $this->definition->getArgument($c - 1);
			$this->arguments[$arg->getName()][] = $token;
		} else {
			$all = $this->definition->getArguments();
			if (count($all)) {
				throw new \RuntimeException(sprintf('Too many arguments, expected arguments: "%s".', implode('" "', array_keys($all))));
			}
			
			throw new \RuntimeException(sprintf('No arguments expected, got "%s".', $token));
		}
	}
	
	/**
	 * Parse a long option.
	 *
	 * @param string $token The current token
	 */
	private function parseLongOption($token)
	{
		$name = substr($token, 2);
		
		if (false !== $pos = strpos($name, '=')) {
			if (0 === strlen($value = substr($name, $pos + 1))) {
				array_unshift($this->parsed, null);
			}
			$this->addLongOption(substr($name, 0, $pos), $value);
		} else {
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
		$name = substr($token, 1);
		
		if (strlen($name) > 1) {
			if ($this->definition->hasShortcut($name[0]) && $this->definition->getOptionForShortcut($name[0])->acceptValue()) {
				$this->addShortOption($name[0], substr($name, '=' === $name[1] ? 2 : 1));
			} else {
				$this->parseShortOptionSet($name);
			}
		} else {
			$next = current($this->parsed);
			if ('-' === $next || '-' !== $next[0]) {
				$this->addShortOption($name, array_shift($this->parsed));
			} else {
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
		$len = strlen($token);
		for ($i = 0; $i < $len; ++$i) {
			if ( ! $this->definition->hasShortcut($token[$i])) {
				if ('=' === $token[$i] && isset($token[$i - 1])) {
					throw new \RuntimeException(sprintf('The "-%s" option accepts no value.', $token[$i - 1]));
				} else {
					throw new \RuntimeException(sprintf('The "-%s" option does not exist.', $token[$i]));
				}
			}
			
			$option = $this->definition->getOptionForShortcut($token[$i]);
			if ($option->acceptValue()) {
				$this->addLongOption($option->getName(), $i == $len - 1 ? null : substr($token, $i + 1));
				
				break;
			} else {
				$this->addLongOption($option->getName(), null);
			}
		}
	}
	
	// }}}
}
