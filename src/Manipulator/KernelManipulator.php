<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Manipulator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator as BaseKernelManipulator;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Changes the PHP code of a Kernel.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class KernelManipulator extends BaseKernelManipulator
{
	/**
	 * The kernel source file split into lines.
	 *
	 * @var array
	 */
	private $lines;
	
	/**
	 * Number of lines the original source file contains.
	 *
	 * @var integer
	 */
	private $lineCount;
	
	public function __construct(KernelInterface $kernel)
	{
		parent::__construct($kernel);
		
		$this->lines = file($this->getFilename());
		$this->lineCount = count($this->lines);
	}
	
	/**
	 * Removes methods from the PHP source of a kernel.
	 * Must be called before replaceUses().
	 *
	 * @param array $methodNames List of method names to remove
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\KernelManipulator
	 */
	public function removeMethods(array $methodNames)
	{
		// Sort methods by starting line number, descending
		$methods = [];
		
		foreach ($methodNames as $methodName) {
			if ( ! $this->reflected->hasMethod($methodName)) {
				continue;
			}
			
			$method = $this->reflected->getMethod($methodName);
			
			$methods[$method->getStartLine() - 1] = $method;
		}
		
		// Remove methods
		if ( ! empty($methods)) {
			krsort($methods);
			
			foreach ($methods as $startLine => $method) {
				$endLine = $method->getEndLine();
				$nextLine = isset($this->lines[$endLine]) ? trim($this->lines[$endLine]) : '';
				
				array_splice($this->lines, $startLine, $endLine - $startLine + (int)(strlen($nextLine) < 1), []);
			}
			
			// Remove trailing empty line in the class body
			$endLine = count($this->lines) - 2;
			if (strlen(trim($this->lines[$endLine])) < 1) {
				unset($this->lines[$endLine]);
			}
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Replace use clauses in the PHP source of a kernel.
	 * Must be called after removeMethods().
	 *
	 * @param array $uses Associative array (old => new) of use clauses to replace; a NULL replacement removes the use clause
	 * @return \Motana\Bundle\MultikernelBundle\Manipulator\KernelManipulator
	 */
	public function replaceUses(array $uses)
	{
		// Replace use clauses
		$this->setCode(token_get_all(implode('', $this->lines)));
		
		// Parse tokens in the source file
		while ($token = $this->next())
		{
			// Break loop when a class token is reached
			if (T_CLASS === $token[0]) {
				break;
			}
			
			// Skip everything else but a use token
			if (T_USE !== $token[0]) {
				continue;
			}
			
			// Next tokens contain the class or namespace name parts
			$parts = [];
			$token = $this->next();
			do {
				if (T_NS_SEPARATOR !== $token[0]) {
					$parts[] = $this->value($token);
				}
				$token = $this->next();
			}
			while (T_AS !== $token[0] && ';' !== $this->value($token));
			
			// Skip use clause if the class or namespace name is not in the list
			$classOrNamespace = implode('\\', $parts);
			if ( ! array_key_exists($classOrNamespace, $uses)) {
				continue;
			}
			
			// Next token is a semicolon or as
			$alias = null;
			$delimiter = $this->value($token);
			if (';' !== $delimiter)
			{
				// Next token is the class or namespace alias
				$alias = ' as ' . $this->value($this->next());
			}
			
			// Remove the use clause if the replacement is a NULL
			if (null === $uses[$classOrNamespace]) {
				unset($this->lines[$this->line]);
			}
			
			// Update use clause with the replacement class name
			else {
				$this->lines[$this->line] = 'use ' . $uses[$classOrNamespace] . $alias . ";\n";
			}
		}
		
		// Return the manipulator
		return $this;
	}
	
	/**
	 * Save the modifications made by removeMethods() and replaceUses().
	 *
	 * @return void
	 */
	public function save()
	{
		// Write the file if its content has changed
		if ($this->lineCount > count($this->lines)) {
			Generator::dump($this->getFilename(), implode('', $this->lines));
		}
	}
}
