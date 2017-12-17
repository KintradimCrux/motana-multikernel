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
use Symfony\Component\Routing\Matcher\TraceableUrlMatcher;
use Symfony\Component\Routing\RouterInterface;

/**
 * Global replacement for the router:match command which also matches the application.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class RouterMatchCommand extends ContainerAwareCommand
{
	/**
	 * Default command name.
	 *
	 * @var string
	 */
	protected static $defaultName = 'router:match';
	
	/**
	 * The router service of the application.
	 *
	 * @var RouterInterface
	 */
	private $router;
	
	/**
	 * Applications for each kernel.
	 *
	 * @var Application[]
	 */
	private $applications = [];
	
	/**
	 * Constructor.
	 *
	 * @param RouterInterface $router The router service of the application
	 */
	public function __construct(RouterInterface $router = null)
	{
		// Do not expect a router service to be present since there is no router service on the boot kernel
		/*
		if ( ! $router instanceof RouterInterface) {
			@trigger_error(sprintf('%s() expects an instance of "%s" as first argument since version 3.4. Not passing it is deprecated and will throw a TypeError in 4.0.', __METHOD__, RouterInterface::class), E_USER_DEPRECATED);
			
			parent::__construct($router);
			
			return;
		}
		*/
		
		parent::__construct();
		
		$this->router = $router;
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		// Not running on the boot kernel
		if ( ! $this->getApplication() instanceof MultikernelApplication)
		{
			// Call parent method if there is a router
			if (null !== $this->router) {
				return parent::isEnabled();
			}
			
			// Not enabled if there is no router service
			if ( ! $this->getContainer()->has('router')) {
				return false;
			}
			
			// Not enabled if the router service is not an instance of RouterInterface
			$router = $this->getContainer()->get('router');
			if ( ! $router instanceof RouterInterface) {
				return false;
			}
			
			// Call parent method
			return parent::isEnabled();
		}
		
		// MultikernelApplication: get the applications for all kernels
		$this->applications = $this->getApplication()->getApplications();
		
		// Check each application if the command is supported
		$enabled = [];
		foreach ($this->applications as $apn => $app) {
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
		$this
		->setDefinition($this->getNativeDefinition())
		->setDescription('Helps debug routes by simulating a path info match')
		->setHelp(<<<'EOF'
The <info>%command.name%</info> shows which routes match a given request and which don't and for what reason:

  <info>php %command.full_name% /foo</info>

or

  <info>php %command.full_name% /foo --method POST --scheme https --host symfony.com --verbose</info>
EOF
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
		
		// Not a MultikernelApplication
		if ( ! $this->getApplication() instanceof MultikernelApplication)
		{
			// BC to be removed in 4.0
			if (null === $this->router) {
				$this->router = $this->getContainer()->get('router');
			}
			
			// Set router context options
			$context = $this->router->getContext();
			if (null !== $method = $input->getOption('method')) {
				$context->setMethod($method);
			}
			if (null !== $scheme = $input->getOption('scheme')) {
				$context->setScheme($scheme);
			}
			if (null !== $host = $input->getOption('host')) {
				$context->setHost($host);
			}
			
			// Get a traceable URL matcher
			$matcher = new TraceableUrlMatcher($this->router->getRouteCollection(), $context);
			
			// Get traces
			$traces = $matcher->getTraces($input->getArgument('path_info'));
			
			// Output a newline
			$io->newLine();
			
			// Process traces
			$matches = false;
			foreach ($traces as $trace)
			{
				// Route almost matches, but...
				if (TraceableUrlMatcher::ROUTE_ALMOST_MATCHES == $trace['level']) {
					$io->text(sprintf('Route <info>"%s"</> almost matches but %s', $trace['name'], lcfirst($trace['log'])));
				}
				
				// Route matches
				elseif (TraceableUrlMatcher::ROUTE_MATCHES == $trace['level']) {
					$io->success(sprintf('Route "%s" matches', $trace['name']));
					
					$routerDebugCommand = $this->getApplication()->find('debug:router');
					$routerDebugCommand->run(new ArrayInput(array('name' => $trace['name'])), $output);
					
					$matches = true;
				}
				
				// Route does not match
				elseif ($input->getOption('verbose')) {
					$io->text(sprintf('Route "%s" does not match: %s', $trace['name'], $trace['log']));
				}
			}
			
			// Found no matching route
			if ( ! $matches) {
				$io->error(sprintf('None of the routes match the path "%s"', $input->getArgument('path_info')));
				
				return 1;
			}
		}
		
		// MultikernelApplication
		else
		{
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
}
