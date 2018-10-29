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

namespace Jupgradenext\Upgrade;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;

use Joomla\Registry\Registry;

use Jupgradenext\Steps\Steps;
use Jupgradenext\Drivers\Drivers;
use Jupgradenext\Models\Checks;
use Jupgradenext\Models\Cleanup;

/**
 * jUpgradeNext utility class for migrations
 *
 * @package		jUpgradeNext
 */
class Upgrade extends UpgradeBase
{
	/**
	 * @var
	 * @since  3.0
	 */
	public $ready = true;

	/**
	 * @var
	 * @since  3.0
	 */
	public $_db = null;

	/**
	 * @var
	 * @since  3.0
	 */
	public $_version = null;

	/**
	 * @var
	 * @since  3.0
	 */
	public $_total = null;

	/**
	 * @var	array
	 * @since  3.0
	 */
	protected $steps = null;

	/**
	 * @var
	 * @since  3.0
	 */
	public $driver = null;

	/**
	 * @var bool Can drop
	 * @since	0.4.
	 */
	public $canDrop = false;

	/**
	 * @var	array
	 * @since  3.0
	 */
	protected $relation = true;

	function __construct(\Joomla\DI\Container $container)
	{
		// Set the current step
		$this->container = $container;
		$this->steps = $container->get('steps');
		$this->_db = $container->get('db');

		if ($this->steps instanceof Steps)
		{
			$this->steps->set('table', $this->getSourceTable());
		}

		// Initialize the driver
		$this->driver = Drivers::getInstance($container);

		// Get the total
		if (!empty($step->source)) {
			$this->_total = UpgradeHelper::getTotal($container);
		}

		// Get the parameters with global settings
		$this->options = $container->get('sites')->getSite();
	}

	/**
	 *
	 * @param   stdClass   $options  Parameters to be passed to the database driver.
	 *
	 * @return  jUpgradeNext  A jUpgradeNext object.
	 *
	 * @since  3.0.0
	 */
	static function loadInstance(\Joomla\DI\Container $container)
	{
		$steps = $container->get('steps');

		// Create our new jUpgradeNext connector based on the options given.
		try
		{

			if (null !== $steps)
			{
				$name = !empty($steps->get('name')) ? $steps->get('name') : '';

				$checks = new Checks($container);
				$version = $checks->checkSite();
				$version = str_replace(".", "", $version);

				// Derive the class name from the driver.
				$class_name = ucfirst(strtolower($name));
				$class = "\\Jupgradenext\\Schemas\\v{$version}\\{$class_name}";

				if (!class_exists($class))
				{
					$class = "\\Jupgradenext\\Schemas\\v{$version}\\Common";
				}

				$xmlpath = !empty($steps->get('xmlpath')) ? $steps->get('xmlpath') : '';
			}

			$instance = new $class($container);
		}
		catch (\Exception $e)
		{
			throw new \Exception(sprintf('Unable to load Steps object: %s', $e->getMessage()));
		}

		return $instance;
	}

	/**
	 * The public entry point for the class.
	 *
	 * @return	boolean
	 * @since	0.4.
	 */
	public function execute()
	{
		try
		{
			$this->upgrade();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return true;
	}

	/**
	 * Sets the data in the destination database.
	 *
	 * @return	void
	 * @since	0.4.
	 * @throws	Exception
	 */
	public function upgrade($rows = false)
	{
		$return = '';
		$name = $this->steps->_getStepName();
		$site = $this->container->get('sites')->getSite();
		$method = $site['method'];

		// Before migrate hook
		if ($this->steps->get('first') == true && $this->steps->get('cid') == 0)
		{
			// Call to truncate table if needed
			try
			{
				if (method_exists($this, 'truncateTable')) {
					$this->truncateTable();
				}
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}

			// Call to hook before migrate
			try
			{
				if (method_exists($this, 'beforeHook')) {
					$this->beforeHook();
				}
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}
		}

		// Get the source data.
		if ($rows === false)
		{
			try
			{
				$rows = $this->driver->getSourceData();
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}
		}

		// Call to database method hook
		if ( $method == 'database')
		{
			if (method_exists($this, 'databaseHook'))
			{
				try
				{
					$rows = $this->databaseHook($rows);
				}
				catch (\Exception $e)
				{
					throw new \Exception($e->getMessage());
				}
			}
		}

		// Call structure hook to create the db table
		if ($this->steps->get('first') == true && $this->steps->get('cid') == 0)
		{
			$structureHook = 'structureHook_'.$name;

			if (method_exists($this, $structureHook)) {
				try
				{
					$this->$structureHook();
				}
				catch (\Exception $e)
				{
					throw new \Exception($e->getMessage());
				}
			}
		}

		// Calling the data modificator hook
		$dataHookFunc = 'dataHook_'.$name;

		// If method exists call the custom dataHook
		if (method_exists($this, $dataHookFunc)) {

			try
			{
				$rows = $this->$dataHookFunc($rows);
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}
		// If method not exists call the default dataHook
		}else{

			try
			{
				$rows = $this->dataHook($rows);
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}
		}

		// Insert the data to the target
		if ($rows !== false) {

			try
			{
				$return = $this->insertData($rows);
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}
		}

		// Load the step object
		$data = $this->steps->load($this->steps->get('name'));

		// Call after migration hook
		if ($this->getTotal() == (int) $this->steps->get('cid'))
		{
			$return = $this->afterHook($rows);
		}

		// Call after all steps hook
		if ($this->steps->get('name') == $this->steps->get('laststep')
		  && $this->steps->get('cache') == 0
			&& $this->getTotal() == $this->steps->get('cid'))
		{
			$return = $this->afterAllStepsHook();
		}

		return $return;
	}

	/**
	 * insertData
	 *
	 * @return	void
	 * @since	3.0.0
	 * @throws	Exception
	 */
	protected function insertData($rows)
	{
		$dbType = $this->container->get('config')->get('dbtype');

		// Get destination table and key
		$table = $this->getDestinationTable();
		$key = $this->getDestKeyName();

		// Replacing the table name if xml exists
		$table = $this->driver->replaceTable($table);

		// Only if array exists
		if (is_array($rows))
		{
			// Get total
			$total = count($rows);

			foreach ($rows as $row)
			{
				if ($row == false)
				{
					$this->steps->_nextID($total);
				}
				else
				{
					// Convert the array into an object.
					$row = (object) $row;

					$saveId = new \stdClass();
					$saveId->table = $table;

					// Check if it exists
					if (($this->relation == true) && (isset($row->$key) && $this->valueExists($row, array($key))))
					{
						$saveId->old_id = isset($row->$key) ? (int) $row->$key : 0;
						unset($row->$key);
					}

					try	{

						if ($dbType == 'postgresql')
						{
							$saveId->new_id = (int) $this->_db->insertObject($table, $row, $key);
						} else {

							$this->_db->insertObject($table, $row, $key);
							$saveId->new_id = (int) $this->_db->insertid();
						}

						if (!empty($saveId->old_id) && $saveId->old_id != $saveId->new_id)
						{
							$this->_db->insertObject('#__jupgradepro_old_ids', $saveId, 'id');
						}

						$this->steps->_nextID($total);

					}	catch (\Exception $e) {

						$this->steps->_nextID($total);
						$this->saveError($e->getMessage());

						continue;
					}
				}
			}

		}else if (is_object($rows)) {

			if ($rows != false) {
				try
				{
					$this->_db->insertObject($table, $rows);
				}
				catch (\Exception $e)
				{
					throw new \Exception($e->getMessage());
				}
			}

		}

		return !empty($this->steps->get('error')) ? false : true;
	}

	/*
	 * Get query condition's
	 *
	 * @return	void
	 * @since	3.0.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['select'] = '*';

		$conditions['where'] = array();

		return $conditions;
	}

	/*
	 * Fake method of dataHook if it not exists
	 *
	 * @return	void
	 * @since	3.0.0
	 * @throws	Exception
	 */
	public function dataHook($rows)
	{
		// Do customisation of the params field here for specific data.
		return $rows;
	}

	/*
	 * Fake method after hooks
	 *
	 * @return	void
	 * @since	3.0.0
	 * @throws	Exception
	 */
	public function afterHook()
	{
		return true;
	}

	/**
	 * Hook to do custom migration after all steps
	 *
	 * @return	boolean Ready
	 * @since	1.1.0
	 */
	protected function afterAllStepsHook()
	{
		return true;
	}

	/*
	 * Method to truncate table
	 *
	 * @return	void
	 * @since		3.8.0
	 * @throws	Exception
	 */
	protected function truncateTable($run = false)
	{
		if ($run == true)
		{
			$cleanup = new Cleanup($this->container);
			$cleanup->truncateTables(array($this->getDestinationTable()));
		}

		return $run;
	}

	/**
	 * @return  string	The destination table key name
	 *
	 * @since   3.0
	 */
	public function getDestKeyName()
	{
		$return = $this->container->get('steps')->get('dest_tbl_key');

		if (empty($return))
		{
			$table = $this->getDestinationTable();

			$query = "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'";
			$this->container->get('db')->setQuery( $query );
			$keys = $this->container->get('db')->loadObjectList();

			$return = !empty($keys) ? $keys[0]->Column_name : '';
		}

		return $return;
	}

	/**
	 * @return  string	The destination table key name
	 *
	 * @since   3.0
	 */
	public function getSourceKeyName()
	{
		$return = $this->steps->get('tbl_key');

		if (empty($return))
		{
			$table = $this->getSourceTable();

			$query = "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'";
			$this->container->get('external')->setQuery( $query );
			$keys = $this->container->get('external')->loadObjectList();

			$return = !empty($keys) ? $keys[0]->Column_name : '';
		}

		return $return;
	}

	/**
	 * @return  bool	Check if the value exists in the table
	 *
	 * @since   3.0
	 */
	public function valueExists($row, $fields, $destination = true)
	{
		if ($destination == true)
		{
			$db = $this->_db;
			$key = $this->getDestKeyName();
			$table = $this->getDestinationTable();
		} else {
			$db = $this->container->get('external');
			$key = $this->getSourceKeyName();
			$table = $this->getSourceTable();
		}

		$extensions = $this->container->get('extensions');

		if (isset($extensions) && $this->container->get('extensions') != false)
		{
			// Replacing the table name from xml
			$replaced_table = $this->driver->replaceTable($table);

			if ($replaced_table != $table) {
				$table = str_replace($table, $replaced_table, $table);
			}
		}

		$key = (!empty($key)) ? $key : 'id';

		// Query
		$query = $db->getQuery(true);
		$query->select($key);
		$query->from($table);

		$conditions = array();

		foreach ($fields as $field)
		{
			if (!empty($row->$field))
			{
				if (is_string($row->$field))
				{
					$cond = $this->_db->quote($row->$field);
				} else {
					$cond = (int)$row->$field;
				}
				$query->where("{$field} = {$cond}");
			}
		}

		$query->setLimit(1);

		$db->setQuery( $query );

		try
		{
			$exists = $db->loadResult();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return empty($exists) ? false : true;
	}

	/**
	 * Internal function to get original database prefix
	 *
	 * @return	an original database prefix
	 * @since	  0.5.3
	 * @throws	Exception
	 */
	public function getMapList($table = '#__categories', $section = false, $custom = false)
	{
		$query = $this->_db->getQuery(true);
		$query->select('new_id, old_id, section');
		$query->from('#__jupgradepro_old_ids');

		$query->where("{$this->_db->qn('table')} = {$this->_db->q($table)}");

		if ($section !== false) {
			$query->where("{$this->_db->qn('section')} = {$this->_db->q($section)}");
		}

		if ($custom !== false) {
			$query->where($custom);
		}

		$this->_db->setQuery($query);

		try
		{
			return $this->_db->loadObjectList('old_id');
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	/**
	 * Internal function to get original database prefix
	 *
	 * @return	an original database prefix
	 * @since	0.5.3
	 * @throws	Exception
	 */
	public function getMapListValue($table = '#__categories', $section = false, $custom = false)
	{
		$query = $this->_db->getQuery(true);
		$query->select('new_id');
		$query->from('#__jupgradepro_old_ids');

		$query->where("{$this->_db->qn('table')} = {$this->_db->q($table)}");

		if ($section !== false)
		{
			if ($section == 'categories')
			{
				$dbType = $this->container->get('config')->get('dbtype');

				if ($dbType == 'postgresql')
				{
					$query->where("({$this->_db->qn('section')} ~ '^[\-\+]?[[:digit:]]*\.?[[:digit:]]*$' OR {$this->_db->qn('section')} = {$this->_db->q('com_section')})");

				}else{
					$query->where("({$this->_db->qn('section')} REGEXP '^[\-\+]?[[:digit:]]*\.?[[:digit:]]*$' OR {$this->_db->qn('section')} = {$this->_db->q('com_section')})");
				}
			}
			else
			{
				$query->where("{$this->_db->qn('section')} = {$this->_db->q($section)}");
			}
		}

		if ($custom !== false) {
			$query->where($custom);
		}

		$this->_db->setQuery($query);

		try
		{
			return $this->_db->loadResult();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	/**
	 * Get the alias if its duplicated
	 *
	 * @param	string	$table	The table to search.
	 * @param	string	$alias	The alias to search.
	 * @param	string	$extension	The extension to filter.
	 *
	 * @return	string	The alias
	 * @since	3.2.1
	 * @throws	Exception
	 */
	public function getAlias($table, $alias, $extension = false)
	{
		$dbType = $this->container->get('config')->get('dbtype');
		$alias = \JFilterOutput::stringURLSafe($alias);

		$query = $this->_db->getQuery(true);
		$query->select('alias');
		$query->from($table);

		if ($extension !== false)
		{
			$query->where("extension = {$this->_db->q($extension)}");
		}

		$sqlAlias = $this->_db->q("^{$alias}$");

		if ($dbType == 'postgresql')
		{
			$query->where("alias LIKE {$sqlAlias}", "OR")->where("alias LIKE '^{$alias}[~]$'");
		} else {
			$query->where("alias RLIKE {$sqlAlias}", "OR")->where("alias RLIKE '^{$alias}[~]$'");
		}

		$query->order('alias DESC');
		$query->setLimit(1);
		$this->_db->setQuery($query);

		try
		{
			return (string) $this->_db->loadResult();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	/**
	 * Converts the params fields into a JSON string.
	 *
	 * @param	string	$params	The source text definition for the parameter field.
	 *
	 * @return	string	A JSON encoded string representation of the parameters.
	 * @since	0.4.
	 * @throws	Exception from the convertParamsHook.
	 */
	protected function convertParams($params, $hook = true)
	{
		$temp	= new Registry($params);
		$object	= $temp->toObject();

		// Fire the hook in case this parameter field needs modification.
		if ($hook === true) {
			$this->convertParamsHook($object);
		}

		return json_encode($object);
	}

	/**
	 * A hook to be able to modify params prior as they are converted to JSON.
	 *
	 * @param	object	$object	A reference to the parameters as an object.
	 *
	 * @return	void
	 * @since	0.4.
	 * @throws	Exception
	 */
	protected function convertParamsHook(&$object)
	{
		// Do customisation of the params field here for specific data.
	}

	/**
	 * Internal function to get the component settings
	 *
	 * @return	an object with global settings
	 * @since	0.5.7
	 */
	public function getParams()
	{
		return $this->options;
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getTotal()
	{
		return (int) $this->steps->get('total');
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getSourceTable()
	{
		$table = $this->steps->getSourceTable();

		return $table;
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getDestinationTable()
	{
		return $this->steps->getDestinationTable();
	}

	/**
	 * Get the new id
	 *
	 * @param	string	$table	The table to search.
	 * @param	int	    $old_id	The old id to search.
	 *
	 * @return	int	The new_id
	 * @since		3.8.0
	 * @throws	Exception
	 */
	public function getNewId($table, $old_id)
	{
		// Get query instance
		$query = $this->_db->getQuery(true);

		// Quote params
		$table = $this->_db->quote($table);
		$old_id = $this->_db->quote($old_id);

		// Query
		$query->select("new_id");
		$query->from("#__jupgradepro_old_ids AS ids");
		$query->where("{$this->_db->qn('table')} = {$table}");
		$query->where("{$this->_db->qn('old_id')} = {$old_id}");
		$query->order("{$this->_db->qn('id')} DESC");
		$query->setLimit(1);

		$this->_db->setQuery($query);

		try
		{
			return (int) $this->_db->loadResult();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	/**
	 * Save old and new id
	 *
	 * @param	string	$table	The table to search.
	 * @param	int	    $old_id	The old id to search.
	 *
	 * @return	int	The new_id
	 * @since		3.8.0
	 * @throws	Exception
	 */
	public function saveNewId($old_id, $new_id, $table = false, $section = false)
	{
		$saveObj = new \stdClass;

		// Quote params
		$saveObj->table = ($table == false) ? $this->getDestinationTable() : $table;
		$saveObj->section = ($section == false) ? '' : $section;
		$saveObj->old_id = (int) $old_id;
		$saveObj->new_id = (int) $new_id;

		// Save old and new id
		try	{
			return $this->_db->insertObject('#__jupgradepro_old_ids', $saveObj);
		}	catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function saveError($error)
	{
		$query = $this->_db->getQuery(true);
		$query->insert('#__jupgradepro_errors')
			->columns("{$this->_db->qn('message')}")
			->values("{$this->_db->q($this->_db->escape($error))}");
		$this->_db->setQuery($query);

		try	{
			$this->_db->execute();
		}	catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		return true;
	}

	/**
	 * Fix the incorrect date to PostgreSQL save
	 *
	 * @param	array   $row	  The row to fix.
	 * @param	array	  $names	The name list to fix.
	 *
	 * @return	array  Return the fixed row.
	 * @since		3.8.0
	 * @throws	Exception
	 */
	protected function fixIncorrectDate($row, $names)
	{

		foreach ($names as $key => &$value)
		{
			if ($row[$value] == '0000-00-00 00:00:00')
			{
				$row[$value] = '1970-01-01 00:00:00';
			}
		}

		return $row;
	}

} // end class
