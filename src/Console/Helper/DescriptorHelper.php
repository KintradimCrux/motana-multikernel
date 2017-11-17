<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Jean-François Simon <contact@jfsimon.fr>
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Console\Helper;

use Motana\Bundle\MultikernelBundle\Console\Descriptor\JsonDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\MarkdownDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\TextDescriptor;
use Motana\Bundle\MultikernelBundle\Console\Descriptor\XmlDescriptor;

use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A replacement for the Symfony Standard Edition descriptor helper.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class DescriptorHelper extends Helper implements ContainerAwareInterface
{
	use ContainerAwareTrait;
	
	/**
	 * Registered descriptors.
	 *
	 * @var DescriptorInterface[]
	 */
	private $descriptors = [];
	
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		// Register the descriptors
		$this->register('txt',new TextDescriptor())
		->register('xml',new XmlDescriptor())
		->register('json',new JsonDescriptor())
		->register('md',new MarkdownDescriptor());
	}
	
	/**
	 * Set the container of the helper.
	 *
	 * @param ContainerInterface $container A ContainerInterface instance
	 */
	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
		
		foreach ($this->descriptors as $descriptor) {
			$descriptor->setContainer($container);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
	 */
	public function getName()
	{
		return 'descriptor';
	}
	
	/**
	 * Describes an object if supported.
	 *
	 * Available display options are:
	 * * format: string, the output format name
	 * * raw_text: boolean, sets output type as raw
	 *
	 * @param OutputInterface $output An OutputInterface instance
	 * @param mixed $object The object to describe
	 * @param array $options Display options
	 * @throws \InvalidArgumentException
	 */
	public function describe(OutputInterface $output, $object, array $options=[])
	{
		// Merge default options with options
		$options = array_merge([
			'raw_text' => false,
			'format' => 'txt',
		], $options);
		
		// Check the specified format is available
		if ( ! isset($this->descriptors[$options['format']])) {
			throw new \InvalidArgumentException(sprintf('Unsupported format "%s".',$options['format']));
		}
		
		// Describe the object
		$descriptor = $this->descriptors[$options['format']];
		$descriptor->describe($output, $object, $options);
	}
	
	/**
	 * Register a descriptor.
	 *
	 * @param string $format Format name
	 * @param DescriptorInterface $descriptor A DescriptorInterface instance
	 * @return \Motana\Component\Console\Helper\DescriptorHelper
	 */
	public function register($format, DescriptorInterface $descriptor)
	{
		$this->descriptors[$format] = $descriptor;
		
		return $this;
	}
}
