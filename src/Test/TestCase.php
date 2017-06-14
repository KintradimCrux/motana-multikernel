<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Test;

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
		if (is_string($classOrObject)) {
			return call_user_func_array('self::callStaticMethod', func_get_args());
		}
		
		$class = new \ReflectionClass($classOrObject);
		$method = $class->getMethod($method);
		
		if ($method->isStatic()) {
			throw new \InvalidArgumentException(sprintf('Cannot invoke static method "%s".', $method->name));
		}
		
		$method->setAccessible(true);
		
		return $method->invokeArgs($classOrObject, $arguments);
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
		$method = new \ReflectionMethod($class, $method);
		
		if ( ! $method->isStatic()) {
			throw new \InvalidArgumentException(sprintf('Cannot invoke non-static method "%s".', $method->name));
		}
		
		$method->setAccessible(true);
		
		return $method->invokeArgs(null, $arguments);
	}
	
	/**
	 * Returns a Filesystem object.
	 *
	 * @return \Symfony\Component\Filesystem\Filesystem
	 */
	public static function getFs()
	{
		if (null !== self::$fs) {
			return self::$fs;
		}
		
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
		if ( ! is_object($object)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'object');
		}
		
		if ( ! is_string($attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
		}
		
		if ( ! preg_match('|^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$|', $attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
		}
		
		try {
			$attribute = new \ReflectionProperty($object, $attributeName);
		} catch (\ReflectionException $e) {
			$reflector = new \ReflectionObject($object);
			while ($reflector = $reflector->getParentClass()) {
				try {
					$attribute = $reflector->getProperty($attributeName);
				} catch (\ReflectionException $e) {
					
				}
			}
		}
		
		if (isset($attribute)) {
			if ($attribute->isStatic()) {
				throw new \PHPUnit_Framework_Exception(sprintf('Cannot access static attribute "%s" as non-static.', $attributeName));
			}
			
			if ( ! $attribute || $attribute->isPublic()) {
				$object->{$attributeName} = $value;
				
				return;
			}
			
			$attribute->setAccessible(true);
			$attribute->setValue($object, $value);
			$attribute->setAccessible(false);
			
			return;
		}
		
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
		if ( ! is_string($className)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
		}
		
		if ( ! class_exists($className)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
		}
		
		if ( ! is_string($attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
		}
		
		if ( ! preg_match('|^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$|', $attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
		}

		try {
			$attribute = new \ReflectionProperty($className, $attributeName);
		} catch (\ReflectionException $e) {
			$reflector = new \ReflectionClass($className);
			while ($reflector = $reflector->getParentClass()) {
				try {
					$attribute = $reflector->getProperty($attributeName);
				} catch (\ReflectionException $e) {
					
				}
			}
		}
		
		if (isset($attribute)) {
			if ( ! $attribute->isStatic()) {
				throw new \PHPUnit_Framework_Exception(sprintf('Cannot access non-static attribute "%s" as static.', $attributeName));
			}
			
			if ( ! $attribute || $attribute->isPublic()) {
				$className::${$attributeName} = $value;
				
				return;
			}
			
			$attribute->setAccessible(true);
			$attribute->setValue(null, $value);
			$attribute->setAccessible(false);
			
			return;
		}
		
		throw new \PHPUnit_Framework_Exception(sprintf('Attribute "%s" not found in object.', $attributeName));
	}
	
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
		if ( ! is_string($attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
		}
		
		if ( ! preg_match('|^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$|', $attributeName)) {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
		}
		
		if (is_string($classOrObject)) {
			if ( ! class_exists($classOrObject)) {
				throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
			}
			
			return static::setStaticAttribute($classOrObject, $attributeName, $value);
		} elseif (is_object($classOrObject)) {
			return static::setObjectAttribute($classOrObject, $attributeName, $value);
		} else {
			throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name or object');
		}
	}
}
