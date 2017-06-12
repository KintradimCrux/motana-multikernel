<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultiKernelBundle\Console\Helper;

use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

use Motana\Bundle\MultiKernelBundle\Console\Descriptor\JsonDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\MarkdownDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\TextDescriptor;
use Motana\Bundle\MultiKernelBundle\Console\Descriptor\XmlDescriptor;

/**
 * A replacement for the Symfony Standard Edition descriptor helper.
 * 
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class DescriptorHelper extends Helper
{
	// {{{ Properties
	
	/**
	 * Registered descriptors.
	 * 
	 * @var DescriptorInterface[]
	 */
	private $descriptors = array();
	
	// }}}
	// {{{ Constructor
	
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
	
	// }}}
	// {{{ Method overrides
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
	 */
	public function getName()
	{
		return 'descriptor';
	}
	
	// }}}
	// {{{ Public methods
	
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
	public function describe(OutputInterface $output, $object, array $options=array())
	{
		$options = array_merge(array(
			'raw_text' => false,
			'format' => 'txt',
		), $options);
		
		if ( ! isset($this->descriptors[$options['format']])) {
			throw new \InvalidArgumentException(sprintf('Unsupported format "%s".',$options['format']));
		}
		
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
	
	// }}}
}
