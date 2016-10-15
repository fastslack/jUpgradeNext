<?php
/**
 * jUpgradeNext
 *
 * @version $Id:
 * @package jUpgradeNext
 * @copyright Copyright (C) 2004 - 2016 Matware. All rights reserved.
 * @author Matias Aguirre
 * @email maguirre@matware.com.ar
 * @link http://www.matware.com.ar/
 * @license GNU General Public License version 2 or later; see LICENSE
 */

namespace JUpgradeNext;

use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\Output\Processor\ColorProcessor;
use Joomla\DI\Container;
use Joomla\Database;

use JUpgradeNext\Upgrade;
use JUpgradeNext\Upgrade\UpgradeHelper;
use JUpgradeNext\Models;

/**
 * The Acme application class.
 *
 * @since  1.0
 */
class CliApplication extends AbstractCliApplication
{
	/**
	 * The application version.
	 *
	 * @var    string
	 * @since  1.0
	 */
	const VERSION = '1.0';

	/**
	 * The application's DI container.
	 *
	 * @var    Container
	 * @since  1.1
	 */
	private $container;

	/**
	* Ascii color array
	*
	* @var array
	* @since 1.0
	*/
	public $_c = array(
		'LIGHT_RED'   => "\033[1;31m",
		'LIGHT_GREEN' => "\033[1;32m",
		'YELLOW'      => "\033[1;33m",
		'LIGHT_BLUE'  => "\033[1;34m",
		'MAGENTA'     => "\033[1;35m",
		'LIGHT_CYAN'  => "\033[1;36m",
		'WHITE'       => "\033[1;37m",
		'NORMAL'      => "\033[0m",
		'BLACK'       => "\033[0;30m",
		'RED'         => "\033[0;31m",
		'GREEN'       => "\033[0;32m",
		'BROWN'       => "\033[0;33m",
		'BLUE'        => "\033[0;34m",
		'CYAN'        => "\033[0;36m",
		'BOLD'        => "\033[1m",
		'UNDERSCORE'  => "\033[4m",
		'REVERSE'     => "\033[7m",
	);

	/**
	 * Init screen
	 *
	 * @return	screen
	 * @since	1.0.0
	 */
	public function screen()
	{
		// Print help
		$this->out();
		$this->out(' jUpgradeNext ' . self::VERSION);
		$this->out();
		$this->out(' Author: Matias Aguirre (maguirre@matware.com.ar)');
		$this->out(' URL: http://www.matware.com.ar');
		$this->out(' License: GNU/GPL http://www.gnu.org/licenses/gpl-2.0-standalone.html');
		$this->out();
	}

	/**
	 * Display the help text.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private function help()
	{
		$this->out('jUpgradeNext ' . self::VERSION);
		$this->out();
		$this->out('Usage:     ./bin/jUpgradeNext -- [switches]');
		$this->out();
		$this->out('Switches:  -h | --help    Prints this usage information.');
		$this->out();
		$this->out('Help:  ./bin/jUpgradeNext -h');
		$this->out();
	}

	/**
	 * Custom initialisation method.
	 *
	 * Called at the end of the AbstractApplication::__construct method. This is for developers to inject initialisation code for their application classes.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	protected function initialise()
	{
		// New DI stuff!
		$container = new Container;
		$input = $this->input;
		$container->share('input', function (Container $c) use ($input) {
			return $input;
		}, true);
		$container->registerServiceProvider(new \Providers\ConfigServiceProvider(APPLICATION_CONFIG));
		$container->registerServiceProvider(new \Providers\LoggerServiceProvider);
		$container->registerServiceProvider(new \Providers\DatabaseServiceProvider);
		$container->registerServiceProvider(new \Providers\ExternalDatabaseServiceProvider);
		$container->registerServiceProvider(new \Providers\StepsServiceProvider);
		$this->container = $container;

		// Maintain configuration API compatibility with \Joomla\Application\AbstractApplication.
		$this->config = $container->get('config');

		// Ensure that required path constants are defined.
		if (!defined('JPATH_ROOT'))
		{
			define('JPATH_ROOT', realpath(dirname(__DIR__)));
		}
	}

	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
		$this->getOutput()->setProcessor(new ColorProcessor);

		$this->screen();

		// Check if help is needed.
		if ($this->input->get('h') || $this->input->get('help'))
		{
			$this->help();
			return;
		}

		// Get configuration
		$config = $this->container->get("config");

		// Running the checks
		$checks = new Models\Checks($this->container);

		try {
			$checks->checks();
		} catch (Exception $e) {
			$checks->returnError (500, $e->getMessage());
		}

		// Running the cleanup
		$cleanup = new Models\Cleanup($this->container);

		try {
			$cleanup->cleanup();
		} catch (Exception $e) {
			$cleanup->returnError (500, $e->getMessage());
		}

		// Migrating Joomla! core
		try {
			$this->migrateCore();
		} catch (Exception $e) {
			$this->returnError (500, $e->getMessage());
		}
/*
		// Migration 3rd party extensions
		try {
			$this->migrateExtensions();
		} catch (Exception $e) {
			$this->returnError (500, $e->getMessage());
		}
*/

	}

	/**
	 * Migrate Joomla! core
	 *
	 * @return	none
	 * @since	2.5.0
	 */
	public function migrateCore()
	{
		$finished = false;
		$method = $this->container->get("config")->get('method');

		$migrate_model = new Models\Migrate($this->container);
		$step_model = new Models\Step($this->container);

		$oldver = UpgradeHelper::getVersion($this->container, 'old');
		$newver = UpgradeHelper::getVersion($this->container, 'new');

		$this->out("{$this->_c['WHITE']}-------------------------------------------------------------------------------------------------");
		$this->out("{$this->_c['WHITE']}|  {$this->_c['BLUE']}	Migrating Joomla! {$oldver} core data to Joomla! {$newver}");

		// Start benchmark
		$benchmark_start = microtime(true);

		while (!$finished)
		{
			$this->out("{$this->_c['WHITE']}-------------------------------------------------------------------------------------------------");

			// Get the current step
			//$step = json_decode($step_model->step());
			$stepsObj = $step_model->step();

			if ($stepsObj instanceof \JUpgradeNext\Steps\Steps)
			{
				$step = $stepsObj->toObject();
			} else {
				break;
			}

//print_r($stepsObj->toObject());
			//if ( null !== ($step->toObject()->id)
			//	|| null === ($step->toObject()->stop)) {
			//	break;
			//}

			$this->out("{$this->_c['WHITE']}|  {$this->_c['GREEN']}[{$step->id}] Migrating {$step->name} (Start:{$step->start} - Stop: {$step->stop} - Total: {$step->total})");
			//echo "{$this->_c['WHITE']}| DEBUG: " . print_r($step);

			// Start benchmark
			$time_start = microtime(true);

			echo "{$this->_c['WHITE']}|  {$this->_c['RED']}[{$this->_c['YELLOW']}";
			if ($step->stop != -1) {
				$response = $migrate_model->migrate($stepsObj);
			}
			$this->out( "{$this->_c['RED']}]" );

			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$this->out( "{$this->_c['WHITE']}|  {$this->_c['CYAN']}[Benchmark] ".round($time, 3)." seconds." );
		}

		$benchmark_end = microtime(true);
		$benchmark = $benchmark_end - $benchmark_start;
		$this->out( "\n{$this->_c['CYAN']}[[TOTAL Benchmark]] ".round($benchmark, 3)." seconds" );

	} // end method

}
