<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Motana\Bundle\MultikernelBundle\Console\Application;
use Motana\Bundle\MultikernelBundle\Console\MultikernelApplication;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Global replacement for the router:match command which also matches the application.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class RouterMatchCommand extends ContainerAwareCommand
{
	/**
	 * Applications for each kernel.
	 *
	 * @var Application[]
	 */
	private $applications = [];
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		// This command is only enabled for a MultikernelApplication
		if ( ! $this->getApplication() instanceof MultikernelApplication) {
			return false;
		}
		
		// Get the applications for all kernels
		$this->applications = $this->getApplication()->getApplications();
		
		// Check each application if the command is supported
		$enabled = [];
		foreach ($this->applications as $app) {
			/** @var Application $app */
			$enabled[] = $app->has('router:match');
		}
		
		// Command is enabled when it is enabled in at least one application
		return in_array(true, $enabled);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::getNativeDefinition()
	 */
	public function getNativeDefinition()
	{
		return new InputDefinition([
			new InputArgument('path_info', InputArgument::REQUIRED, 'A path info'),
			new InputOption('method', null, InputOption::VALUE_REQUIRED, 'Sets the HTTP method'),
			new InputOption('scheme', null, InputOption::VALUE_REQUIRED, 'Sets the URI scheme (usually http or https)'),
			new InputOption('host', null, InputOption::VALUE_REQUIRED, 'Sets the URI host'),
		]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
	{
		$this->setName('router:match')
		->setDefinition($this->getNativeDefinition())
		->setDescription('Helps debug routes by simulating a path info match')
		->setHelp(<<<EOH
The <info>%command.name%</info> shows which routes match a given request and which don't and for what reason:

  <info>php %command.full_name% /foo</info>

or

  <info>php %command.full_name% /foo --method POST --scheme https --host symfony.com --verbose</info>
EOH
		);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Create a SymfonyStyle instance for message output
		$io = new SymfonyStyle($input, $output);
		
		// Get the path info argument
		$pathInfo = $input->getArgument('path_info');
		
		// Find the application with a kernel name matching the path info
		$matchedApplication = null;
		$matchedApplicationName = null;
		foreach ($this->applications as $kernelName => $application) {
			if (0 === strpos($pathInfo, '/' . $kernelName)) {
				$matchedApplication = $application;
				$matchedApplicationName = $kernelName;
			} elseif ($input->getOption('verbose')) {
				$io->text(sprintf('Application "%s" does not match: Path "/%s" does not match', $kernelName, $kernelName));
			}
		}
		
		// Print an error message if no kernel matches the path info
		if (null === $matchedApplication) {
			$io->error(sprintf('No application matches the path prefix "%s"', substr($pathInfo, 0, strpos($pathInfo, '/', 1) ?: strlen($pathInfo))));
			return 1;
		}
		
		// Get the container of the matched application
		$container = $matchedApplication->getKernel()->getContainer();
		
		// Print an error message if the matched application does not have a router
		if ( ! $container->has('router')) {
			$io->error(sprintf('Matched application "%s" does not have a router', $matchedApplicationName));
			return 1;
		}
		
		// Print a message indicating we have a matching application
		$io->success(sprintf('Application "%s" matches', $matchedApplicationName));
		
		// Generate parameters for the router:match command of the application
		$params = [
			'path_info' => substr($pathInfo, 1 + strlen($matchedApplicationName)) ?: '/'
		];
		foreach ($input->getOptions() as $option => $value) {
			if (null !== $value) {
				$params['--' . $option] = $value;
			}
		}
		
		// Run the router:match command of the application
		$routerMatchCommand = $matchedApplication->find('router:match');
		return $routerMatchCommand->run(new ArrayInput($params), $output);
	}
}
