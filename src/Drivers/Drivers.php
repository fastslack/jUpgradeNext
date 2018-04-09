<?php
/**
 * jUpgradeNext
 *
 * @version $Id:
 * @package jUpgradeNext
 * @copyright Copyright (C) 2004 - 2018 Matware. All rights reserved.
 * @author Matias Aguirre
 * @email maguirre@matware.com.ar
 * @link http://www.matware.com.ar/
 * @license GNU General Public License version 2 or later; see LICENSE
 */

namespace Jupgradenext\Drivers;

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
	}

	/**
	 *
	 * @param   stdClass   $options  Parameters to be passed to the database driver.
	 *
	 * @return  jUpgradeNext  A jUpgradeNext object.
	 *
	 * @since   3.0.0
	 * @deprecated  3.8.0
	 */
	static function getInstance(\Joomla\DI\Container $container)
	{
		// Get site params
		$site = $container->get('sites')->getSite();

		// Derive the class name from the driver.
		$class_name = 'Drivers' . ucfirst(strtolower($site['method']));
		$class_name = '\\Jupgradenext\\Drivers\\' . $class_name;

		// If the class still doesn't exist we have nothing left to do but throw an exception.  We did our best.
		if (!class_exists($class_name))
		{
			throw new Exception(sprintf('Unable to load Database Driver: %s', $site['method']));
		}

		// Create our new jUpgradeDriver connector based on the options given.
		try
		{
			$instance = new $class_name($container);
		}
		catch (Exception $e)
		{
			throw new Exception(sprintf('Unable to load jUpgradeNext object: %s', $e->getMessage()));
		}

		return $instance;
	}

	/**
	 * Get table structure
	 *
	 * @return  string  The table name
	 *
	 * @since   3.0.0
	 */
	public function getStructure($table)
	{
		// Get site params
		$site = $this->container->get('sites')->getSite();

		// Get the structure
		if ($site['method'] == 'database')
		{
			$result = $this->container->get('external')->getTableCreate($table);
			$structure = str_replace($this->container->get('external')->getPrefix(), "#__", "{$result[$table]} ;\n\n");
		}
		else if ($site['method'] == 'restful')
		{
			if (strpos($table, '#__') === false)
			{
				$table = '#__'.$table;
			}

			$structure = $this->requestRest("tablestructure", $table);
		}

		return $structure;
	}

	/**
	 * getSource
	 *
	 * @return	array	The requested data
	 * @since	3.0.0
	 * @throws	Exception
	 */
	public function getSourceData($table = null, $chunk = null)
	{
		// Init rows variable
		$rows = array();

		// Get the method and chunk
		$site = $this->container->get('sites')->getSite();
		$method = $site['method'];
		$chunk = $site['chunk_limit'];

		switch ($method) {
			case 'restful':

				$table = ($table == null) ? $this->container->get('steps')->get('source') : $table;

				if (strpos($table, '#__') === false)
				{
					$table = '#__'.$table;
				}

				$rows = $this->getSourceDataRestList($table, $chunk);
		    break;
			case 'database':
		    $rows = $this->getSourceDatabase();
		    break;
		}

		return $rows;
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
		return $this->container->get('steps')->get('cid');
	}

	/**
	 * @return  string	The step name
	 *
	 * @since   3.0
	 */
	public function _getStepName()
	{
		return $this->container->get('steps')->get('name');
	}
}
