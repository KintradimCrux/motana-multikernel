<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Motana\Bundle\MultiKernelBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Motana\Bundle\MultiKernelBundle\DependencyInjection\MotanaMultiKernelExtension;
use Motana\Bundle\MultiKernelBundle\MotanaMultiKernelBundle;
use Motana\Bundle\MultiKernelBundle\Test\TestCase;

/**
 * @coversDefaultClass Motana\Bundle\MultiKernelBundle\DependencyInjection\MotanaMultiKernelExtension
 */
class MotanaMultiKernelExtensionTest extends TestCase
{
	/**
	 * @var MotanaMultiKernelExtension
	 */
	protected $extension;
	
	/**
	 * @var array
	 */
	protected $classes;
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp()
	{
		$this->extension = new MotanaMultiKernelExtension();
		$this->classes = $this->getExcludedClasses();
	}
	
	/**
	 * Loads the class cache configuration and returns the classes listed in it.
	 * 
	 * @return array
	 */
	protected function getExcludedClasses()
	{
		$classes = array();
		$bundle = new MotanaMultiKernelBundle();
		
		if (is_file($file = $bundle->getPath() . '/Resources/config/class_cache.xml')) {
			$xml = new \SimpleXMLElement($file, LIBXML_NOCDATA, true);
			foreach ($xml->{'parameters'}->{'parameter'}->{'parameter'} as $parameter) {
				$classes[] = (string) $parameter;
			}
		}
		
		return $classes;
	}
	
	/**
	 * @covers ::load()
	 */
	public function testLoad()
	{
		$config = array(
			'default' => 'app',
		);
		
		$container = new ContainerBuilder();
		$this->extension->load(array($config), $container);
		
		// Check the extension inserted the default kernel parameter
		$this->assertTrue($container->hasParameter('motana_multi_kernel.default'));
		
		// Check the default kernel parameter has the correct value
		$this->assertEquals($config['default'], $container->getParameter('motana_multi_kernel.default'));
		
		// Check the extension loaded the class cache exclude parameter
		$this->assertTrue($container->hasParameter('motana_multi_kernel.class_cache.exclude'));
		
		// Check the class cache exclude parameter has the correct value
		$this->assertEquals($this->classes, $container->getParameter('motana_multi_kernel.class_cache.exclude'));
	}
	
	/**
	 * @covers ::getXsdValidationBasePath()
	 */
	public function testGetXsdValidationBasePath()
	{
		$class = new \ReflectionClass($this->extension);
		$expected = dirname($class->getFileName()) . '/../Resources/config/schema';
		
		$this->assertEquals($expected, $this->extension->getXsdValidationBasePath());
	}
	
	/**
	 * @covers ::getNamespace()
	 */
	public function testGetNamespace()
	{
		$this->assertEquals('http://symfony.com/schema/dic/motana_multi_kernel', $this->extension->getNamespace());
	}
}
