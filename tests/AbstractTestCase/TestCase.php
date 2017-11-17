<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Tests\AbstractTestCase;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for test.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Filesystem
	 */
	protected static $fs;
	
	/**
	 * Utility method to invoke a non-public method of a class instance.
	 *
	 * @param object $classOrObject Object instance or class name
	 * @param string $method Method name
	 * @param mixed ...$arguments Arguments for the method call
	 * @throws \InvalidArgumentException
	 * @return mixed
	 */
	public static function callMethod($classOrObject, $method, ...$arguments)
	{
		// Class or method is a string
		if (is_string($classOrObject)) {
			return call_user_func_array('self::callStaticMethod', func_get_args());
		}
		
		// Inspect the specified method
		$class = new \ReflectionClass($classOrObject);
		$method = $class->getMethod($method);
		
		// Check the method is not static
		if ($method->isStatic()) {
			throw new \InvalidArgumentException(sprintf('Cannot invoke static method "%s".', $method->name));
		}
		
		// Make the method accessible
		if ( ! $method->isPublic()) {
			$method->setAccessible(true);
		}
		
		// Invoke the method
		$returnValue = $method->invokeArgs($classOrObject, $arguments);
		
		// Make the method inaccessible
		if ( ! $method->isPublic()) {
			$method->setAccessible(false);
		}
		
		// Return the return value of the method
		return $returnValue;
	}
	
	/**
	 * Utility method to invoke a non-public static method of a class.
	 *
	 * @param string $class Class name
	 * @param string $method Method name
	 * @param mixed ...$arguments Arguments for the method call
	 * @throws \InvalidArgumentException
	 * @return mixed
	 */
	public static function callStaticMethod($class, $method, ...$arguments)
	{
		// Inspect the specified method
		$method = new \ReflectionMethod($class, $method);
		
		// Check the method is static
		if ( ! $method->isStatic()) {
			throw new \InvalidArgumentException(sprintf('Cannot invoke non-static method "%s".', $method->name));
		}
		
		// Make the method accessible
		if ( ! $method->isPublic()) {
			$method->setAccessible(true);
		}
		
		// Invoke the method
		$returnValue = $method->invokeArgs(null, $arguments);
		
		// Make the method inaccessible
		if ( ! $method->isPublic()) {
			$method->setAccessible(false);
		}
		
		// Return the return value of the method
		return $returnValue;
	}
	
	/**
	 * Returns a Filesystem object.
	 *
	 * @return \Symfony\Component\Filesystem\Filesystem
	 */
	public static function getFs()
	{
		// Return existing instance if available
		if (null !== self::$fs) {
			return self::$fs;
		}
		
		// Create and return a new instance
		return self::$fs = new Filesystem();
	}

	/**
	 * Set the value of an object attribute.
	 * This also works for attributes that are declared protected or private.
	 *
	 * @param object $object
	 * @param string $attributeName
	 * @param mixed $value
	 * @throws \PHPUnit_Framework_Exception
	 */
	public static function setObjectAttribute($object, $attributeName, $value)
	{
		// Check the specified object is an object
		if ( ! is_object($object)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'object');
		}
		
		// Check the specified attribute name is a string
		if ( ! is_string($attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
		}
		
		// Check the specified attribute name is valid
		if ( ! preg_match('|^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$|', $attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
		}
		
		// Try to get a ReflectionProperty object for the attribute
		try {
			$attribute = new \ReflectionProperty($object, $attributeName);
		} catch (\ReflectionException $e) {
			$reflector = new \ReflectionObject($object);
			while ($reflector = $reflector->getParentClass()) {
				try {
					$attribute = $reflector->getProperty($attributeName);
					
					break;
				} catch (\ReflectionException $e) {
				}
			}
		}

		// Found an attribute
		if (isset($attribute))
		{
			// Check the attribute is not static
			if ($attribute->isStatic()) {
				throw new \PHPUnit_Framework_Exception(sprintf('Cannot access static attribute "%s" as non-static.', $attributeName));
			}
			
			// Attribute is public, return its value
			if ( ! $attribute || $attribute->isPublic()) {
				$object->{$attributeName} = $value;
				return;
			}
			
			// Make the attribute accessible
			$attribute->setAccessible(true);
			
			// Set the attribute value
			$attribute->setValue($object, $value);
			
			// Make the attribute inaccessible
			$attribute->setAccessible(false);

			// Return to caller
			return;
		}
		
		// Attribute unknown, throw an exception
		throw new \PHPUnit_Framework_Exception(sprintf('Attribute "%s" not found in object.', $attributeName));
	}
	
	/**
	 * Set the value of a static attribute.
	 * This also works for attributes that are declared protected or private.
	 *
	 * @param string $className
	 * @param string $attributeName
	 * @param mixed $value
	 * @throws \PHPUnit_Framework_Exception
	 */
	public static function setStaticAttribute($className, $attributeName, $value)
	{
		// Check the specified class name is a string
		if ( ! is_string($className)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
		}
		
		// Check the specified class exists
		if ( ! class_exists($className)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
		}
		
		// Check the specified attribute name is a string
		if ( ! is_string($attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
		}
		
		// Check the specified attribute name is valid
		if ( ! preg_match('|^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$|', $attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
		}

		// Try to get a ReflectionProperty object for the attribute
		try {
			$attribute = new \ReflectionProperty($className, $attributeName);
		} catch (\ReflectionException $e) {
			$reflector = new \ReflectionClass($className);
			while ($reflector = $reflector->getParentClass()) {
				try {
					$attribute = $reflector->getProperty($attributeName);
					
					break;
				} catch (\ReflectionException $e) {
				}
			}
		}
		
		// Found an attribute
		if (isset($attribute))
		{
			// Check the attribute is static
			if ( ! $attribute->isStatic()) {
				throw new \PHPUnit_Framework_Exception(sprintf('Cannot access non-static attribute "%s" as static.', $attributeName));
			}
			
			// Attribute is public, return its value
			if ( ! $attribute || $attribute->isPublic()) {
				$className::${$attributeName} = $value;
				
				return;
			}
			
			// Make the attribute accessible
			$attribute->setAccessible(true);
			
			// Set the attribute value
			$attribute->setValue(null, $value);
			
			// Make the attribute inaccessible
			$attribute->setAccessible(false);
			
			// Return to caller
			return;
		}
		
		// Attribute unknown, throw an exception
		throw new \PHPUnit_Framework_Exception(sprintf('Attribute "%s" not found in object.', $attributeName));
	// @codeCoverageIgnoreStart
	}
	// @codeCoverageIgnoreEnd
	
	/**
	 * Set the value of an attribute of a class or an object.
	 * This also works for attributes that are declared protected or private.
	 *
	 * @param string|object $classOrObject
	 * @param string $attributeName
	 * @param mixed $value
	 * @throws \PHPUnit_Framework_Exception
	 */
	public static function writeAttribute($classOrObject, $attributeName, $value)
	{
		// Check the specified attribute name is a string
		if ( ! is_string($attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
		}
		
		// Check the specified attribute name is valid
		if ( ! preg_match('|^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$|', $attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
		}
		
		// Class name was specified
		if (is_string($classOrObject))
		{
			// Check the specified class exists
			if ( ! class_exists($classOrObject)) {
				throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
			}
			
			// Set the attribute value
			return static::setStaticAttribute($classOrObject, $attributeName, $value);
		}
		
		// Object was specified, set the attribute value
		if (is_object($classOrObject)) {
			return static::setObjectAttribute($classOrObject, $attributeName, $value);
		}
		
		// Invalid type for first parameter, throw an exception
		throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name or object');
	}
}
