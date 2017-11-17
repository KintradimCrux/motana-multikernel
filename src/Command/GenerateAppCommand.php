<?php

/*
 * This file is part of the Motana Multi-Kernel Bundle, which is licensed
 * under the MIT license. For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 *
 * (c) Wenzel Jonas <mail@ramihyn.sytes.net>
 */

namespace Motana\Bundle\MultikernelBundle\Command;

use Motana\Bundle\MultikernelBundle\Generator\AppGenerator;
use Motana\Bundle\MultikernelBundle\Generator\Model\App;
use Motana\Bundle\MultikernelBundle\HttpKernel\BootKernel;

use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Sensio\Bundle\GeneratorBundle\Command\Validators as SensioValidators;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Command for creating a new symfony app in a multi-kernel environment.
 *
 * @author Wenzel Jonas <mail@ramihyn.sytes.net>
 */
class GenerateAppCommand extends GeneratorCommand
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::isEnabled()
	 */
	public function isEnabled()
	{
		// Command is enabled on the boot kernel in dev and test environment
		return $this->getApplication()->getKernel() instanceof BootKernel
			&& in_array($this->getApplication()->getKernel()->getEnvironment(), [ 'dev', 'test' ]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
	{
		$this->setName('generate:app')
		->setDescription('Generates an app')
		->setDefinition(new InputDefinition([
			new InputOption('kernel', null, InputOption::VALUE_REQUIRED, 'App kernel name'),
			new InputOption('dir', null, InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', 'src/'),
			new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Use the format for bundle configuration files (php, xml, yml, or annotation)'),
			new InputOption('micro', null, InputOption::VALUE_NONE, 'Generate a microkernel app'),
			new InputOption('no-bundle', null, InputOption::VALUE_NONE, 'Skip generating a bundle for the app'),
		]))
		->setHelp(<<<EOH
The <info>%command.name%</info> command helps you generates new apps.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--kernel</comment> is the only one needed if you follow the
conventions):

<info>php %command.full_name% --kernel=app</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php %command.full_name% --kernel=foo --dir=src [--no-bundle] --no-interaction</info>

The names of the generated kernel, cache and bundle class names are generated from the camelized kernel name.

EOH
		);
	}

	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::interact()
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		// Project directory is the working directory
		$projectDir = getcwd();
		
		// Print the headline
		$questionHelper = $this->getQuestionHelper();
		$questionHelper->writeSection($output, 'Welcome to the multi-kernel app generator!');
		
		/*
		 * kernel option
		 */
		$kernel = $input->getOption('kernel');
		
		$output->writeln([
			'',
			'',
			'',
		]);
		
		// ask, but use $kernel as default
		$question = new Question($questionHelper->getQuestion(
			'App kernel name',
			$kernel
		), $kernel);
		$question->setValidator(function($answer) use ($projectDir) {
			return Validators::validateNewKernelName($answer, $projectDir);
		});
		
		$kernel = $questionHelper->ask($input, $output, $question);
		
		$input->setOption('kernel', $kernel);
		
		/*
		 * dir option
		 */
		// defaults to src/ in the option
		$dir = $input->getoption('dir');
		
		$output->writeln([
			'',
			'App bundles are usually generated into the <info>src/</info> directory. Unless you\'re',
			'doing something custom, hit enter to keep this default!',
			'',
		]);
		
		$question = new Question($questionHelper->getQuestion(
			'Target directory',
			$dir
		), $dir);
		$question->setValidator(function($answer){
			return Validators::validateRelativePath($answer);
		});
		
		$dir = $questionHelper->ask($input, $output, $question);
		
		$input->setOption('dir', $dir);
		
		/*
		 * format option
		 */
		$format = $input->getOption('format');
		
		if ( ! $format) {
			$format = 'annotation';
		}
		
		$output->writeln([
			'',
			'What format do you want to use for your generated configuration?',
			'',
		]);
		
		$question = new Question($questionHelper->getQuestion(
			'Configuration format (annotation, yml, xml, php)',
			$format
		), $format);
		$question->setAutocompleterValues([ 'annotation', 'yml', 'xml', 'php' ]);
		$question->setValidator(function($answer){
			return SensioValidators::validateFormat($answer);
		});
		
		$format = $questionHelper->ask($input, $output, $question);
		
		$input->setOption('format', $format);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Get the question helper
		$questionHelper = $this->getQuestionHelper();
		
		// Get the app model
		$app = $this->createAppObject($input);
		
		// Get the app generator
		$generator = $this->getGenerator();
		
		// Output the headlines
		$questionHelper->writeSection($output, 'App generation');
		$output->writeln(vsprintf('> Generating a sample app skeleton into <info>%s</info>', [
			$this->makePathRelative($app->getProjectDirectory())
		]));
		
		// Generate the app skeleton
		$generator->generateApp($app);
		
		// Output the summary
		$questionHelper->writeGeneratorSummary($output, []);
	}
	
	/**
	 * Returns the question helper.
	 *
	 * @return QuestionHelper
	 */
	protected function getQuestionHelper()
	{
		// Get the question helper
		$question = $this->getHelperSet()->get('question');
		
		// Create a new question helper instance if required
		if ( ! $question || get_class($question) !== 'Sensio\\Bundle\\GeneratorBundle\\Command\\Helper\\QuestionHelper') {
			$this->getHelperSet()->set($question = new QuestionHelper());
		}
		
		// Return the question helper
		return $question;
	}
	
	/**
	 * Creates an App model from input parameters.
	 *
	 * @param InputInterface $input An InputInterface instance
	 * @throws \RuntimeException
	 * @return \Motana\Bundle\MultikernelBundle\Generator\Model\App
	 */
	protected function createAppObject(InputInterface $input)
	{
		// Check required options are specified
		foreach ([ 'kernel', 'dir' ] as $option) {
			if (null === $input->getOption($option)) {
				throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
			}
		}
		
		// Pre-process the kernel name
		$kernelName = $input->getOption('kernel');
		$kernelCamel = BootKernel::camelizeKernelName($kernelName);
		
		// Get parameters for the model
		$generateBundle = ! $input->getOption('no-bundle');
		$microkernel = $input->getOption('micro');
		$dir = $input->getOption('dir');
		$format = $input->getOption('format');
		
		// Get the absolute path of the source directory
		if ( ! $this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
			$dir = getcwd() . '/' . $dir;
		}
		
		// Append a slash to the directory if required
		$dir = '/' === substr($dir, -1, 1) ? $dir : $dir . '/';
		
		// Return the app model
		return new App(
			$projectDirectory = getcwd(),
			$kernelName,
			$multikernel = true,
			$generateBundle,
			$bundleNamespace = $kernelCamel . 'Bundle',
			$bundleName = $kernelCamel . 'Bundle',
			$dir,
			$format,
			$microkernel
		);
	}
	
	/**
	 * Creates the generator used by the command.
	 *
	 * @return \Motana\Bundle\MultikernelBundle\Generator\AppGenerator
	 */
	protected function createGenerator()
	{
		return new AppGenerator();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand::getSkeletonDirs()
	 */
	protected function getSkeletonDirs(BundleInterface $bundle = null)
	{
		// Get the SensioGeneratorBundle skeleton directories
		$skeletonDirs = parent::getSkeletonDirs($bundle);
		
		// Add override skeleton directories of a bundle
		if (isset($bundle) && is_dir($dir = $bundle->getPath() . '/Resources/MotanaMultikernelBundle/skeleton')) {
			$skeletonDirs[] = $dir;
		}
		
		if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir() . '/Resources/MotanaMultikernelBundle/skeleton')) {
			$skeletonDirs[] = $dir;
		}
		
		// Add skeleton directory of MotanaMultikernelBundle
		$skeletonDirs[] = __DIR__ . '/../Resources/skeleton';
		$skeletonDirs[] = __DIR__ . '/../Resources';
		
		// Return the skeleton directories
		return $skeletonDirs;
	}
}
