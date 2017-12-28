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

namespace Jupgradenext\Drivers;

use Jupgradenext\Schemas\v15;
use Jupgradenext\Steps\Steps;

/**
 * jUpgradeNext driver class
 *
 * @package		MatWare
 * @subpackage	com_jupgradenext
 */
class Drivers
{
	/**
	 * @var
	 * @since  3.0
	 */
	public $params = null;

	/**
	 * @var
	 * @since  3.0
	 */
	public $_db = null;

	/**
	 * @var	array
	 * @since  3.0
	 */
	protected $_step = null;

	function __construct(\Joomla\DI\Container $container)
	{
		$this->container = $container;
		$this->options = $container->get('config');

		$this->_steps = $container->get('steps');

		// Creating dabatase instance for this installation
		$this->_db = $container->get('db');
	}

	/**
	 *
	 * @param   stdClass   $options  Parameters to be passed to the database driver.
	 *
	 * @return  jUpgradeNext  A jUpgradeNext object.
	 *
	 * @since  3.0.0
	 */
	static function getInstance(\Joomla\DI\Container $container)
	{
		// Get the params and Joomla version web and cli
		$options = $container->get('config');

		// Derive the class name from the driver.
		$class_name = 'Drivers' . ucfirst(strtolower($options->get('method')));
		$class_name = '\\Jupgradenext\\Drivers\\' . $class_name;

		// If the class still doesn't exist we have nothing left to do but throw an exception.  We did our best.
		if (!class_exists($class_name))
		{
			throw new \RuntimeException(sprintf('Unable to load Database Driver: %s', $options->get('method')));
		}

		// Create our new jUpgradeDriver connector based on the options given.
		try
		{
			$instance = new $class_name($container, $options);
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(sprintf('Unable to load jUpgradeNext object: %s', $e->getMessage()));
		}

		return $instance;
	}

	/**
	 * Update the step id
	 *
	 * @return  int  The next id
	 *
	 * @since   3.0.0
	 */
	public function _getStepID()
	{
		return $this->_steps->get('cid');
	}

	/**
	 * @return  string	The step name
	 *
	 * @since   3.0
	 */
	public function _getStepName()
	{
		return $this->_steps->get('name');
	}
}
