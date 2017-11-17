<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Input;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * An input argument that has a condition.
 *
 * A conditional argument is inserted in the {@link InputDefinition} like every other argument.
 * When binding the {@link ArgvInput} to an {@link InputDefinition}, the condition is evaluated
 * and the argument is added.
 *
 * The value of the argument is either its default value (when the condition result is FALSE)
 * or an argument from the commandline.
 *
 * Note: if using a Symfony ArgvInput, the condition is not evaluated and the argument behaves
 * like a regular {@link InputArgument}.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class ConditionalArgument extends InputArgument
{
	/**
	 * Closure to execute instead of the condition() method.
	 *
	 * @var \Closure
	 */
	private $code;
	
	/**
	 * Value processed for the evaluated condition.
	 *
	 * @var mixed
	 */
	private $value;
	
	/**
	 * Cached result of the evaluated condition.
	 *
	 * @var boolean
	 */
	private $result;
	
	/**
	 * Default value.
	 *
	 * @var string
	 */
	private $default;
	
	/**
	 * Constructor.
	 *
	 * @param string $name Argument name
	 * @param integer $mode Argument mode
	 * @param string $description Argument description
	 * @param string $default Argument default value
	 * @throws InvalidArgumentException
	 */
	public function __construct($name, $mode = null, $description = '', $default = null)
	{
		parent::__construct($name, $mode, $description);
		
		$this->default = $default;
	}
	
	/**
	 * Set the Closure to execute instead of the condition() method.
	 *
	 * @param Closure $code Closure to execute
	 */
	public function setCode(\Closure $code)
	{
		// Set the closure condition and reset any previous value and condition result
		$this->code = $code;
		$this->value = null;
		$this->result = null;
	}
	
	/**
	 * Returns the Closure to execute instead of the condition() method.
	 *
	 * @return \Closure
	 */
	public function getCode()
	{
		return $this->code;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Motana\Component\Console\Input\InputArgument::getDefault()
	 */
	public function getDefault()
	{
		// Return the default value if the argument is not required
		if ( ! $this->isRequired()) {
			return $this->default;
		}
	}
	
	/**
	 * Returns the boolean result of the evaluated condition.
	 *
	 * @param mixed $value Argument value to process
	 * @return boolean
	 */
	public function getResult($value)
	{
		// Value is unchanged and the condition is already evaluated, return cached result
		if ($value === $this->value && null !== $this->result) {
			return $this->result;
		}
		
		// Evaluate the condition and return its result
		return $this->evaluateCondition($value);
	}
	
	/**
	 * Returns a boolean indicating whether the argument is required or not.
	 *
	 * @param mixed $value Argument value to process
	 * @throws \LogicException
	 */
	protected function condition($value)
	{
		throw new \LogicException('You must override the condition() method in the concrete input class or use the setCode() method.');
	}
	
	/**
	 * Evaluates the required condition.
	 *
	 * @param mixed $value Argument value to process
	 * @return boolean
	 */
	private function evaluateCondition($value)
	{
		// Store the value
		$this->value = $value;
		
		// Call the closure to evaluate the condition, if available
		if (is_callable($this->code)) {
			$r = new \ReflectionMethod($this, 'condition');
			if ($r->getDeclaringClass()->getName() === self::class) {
				return $this->result = (boolean) call_user_func($this->code, $value);
			}
		}
		
		// Call condition() to evaluate the condition
		return $this->result = (boolean) $this->condition($value);
	}
}
