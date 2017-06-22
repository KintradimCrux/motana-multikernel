<?php

/*
 * This file is part of the Motana package.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Motana\Bundle\MultikernelBundle\Console\Output;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpKernel\Kernel;

/**
 * A buffered output that replaces the Symfony version number with a placeholder.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 *
 */
class BufferedOutput extends Output
{
	/**
	 * @var string
	 */
	private $buffer = '';
	
	/**
	 * Empties the buffer and returns buffered content.
	 *
	 * @return string
	 */
	public function fetch()
	{
		$content = $this->buffer;
		$this->buffer = '';
		
		return $content;
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function doWrite($message, $newline)
	{
		$this->buffer .= str_replace(Kernel::VERSION, '[symfony-version]', $message);
		
		if ($newline) {
			$this->buffer .= PHP_EOL;
		}
	}
}
