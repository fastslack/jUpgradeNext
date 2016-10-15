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

namespace JUpgradeNext\Upgrade;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;

use Joomla\Registry\Registry;

use JUpgradeNext\Steps\Steps;
use JUpgradeNext\Drivers\Drivers;

/**
 * jUpgradePro utility class for migrations
 *
 * @package		Matware
 * @subpackage	com_jupgradepro
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
	 * @var    array  List of extensions steps
	 * @since  12.1
	 */
	private $extensions_steps = array('extensions', 'ext_components', 'ext_modules', 'ext_plugins');

	/**
	 * @var bool Can drop
	 * @since	0.4.
	 */
	public $canDrop = false;

	function __construct(\Joomla\DI\Container $container)
	{
		// Set the current step
		$this->container = $container;
		$this->steps = $container->get('steps');
		$this->options = $container->get('config');
		$this->_db = $container->get('db');

		if ($this->steps instanceof Steps) {
			$this->steps->set('table', $this->getSourceTable());
		}

		// Initialize the driver
		$this->driver = Drivers::getInstance($container);

		// Get the total
		if (!empty($step->source)) {
			$this->_total = UpgradeHelper::getTotal($container);
		}

		// Set timelimit to 0
		if(!@ini_get('safe_mode')) {
			if (!empty($this->options->get('timelimit'))) {
				set_time_limit(0);
			}
		}

		// Make sure we can see all errors.
		if (!empty($this->options->get('error_reporting'))) {
			error_reporting(E_ALL);
			@ini_set('display_errors', 1);
		}

		// MySQL grants check
		$query = "SHOW GRANTS FOR CURRENT_USER";
		$this->_db->setQuery( $query );
		$list = $this->_db->loadRowList();
		$grant = empty($list[1][0]) ? $list[0][0] : $list[1][0];

		if (strpos($grant, 'DROP') == true || strpos($grant, 'ALL') == true) {
			$this->canDrop = true;
		}
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

				$version = $steps->get('version');
				$version = str_replace(".", "", $version);

				// Derive the class name from the driver.
				$class_name = ucfirst(strtolower($name));
				$class = "\\JUpgradeNext\\Schemas\\v{$version}\\{$class_name}";

				$xmlpath = !empty($steps->get('xmlpath')) ? $steps->get('xmlpath') : '';
			}

			$instance = new $class($container);
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(sprintf('Unable to load Steps object: %s', $e->getMessage()));
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
			//$this->upgrade();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
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
		$name = $this->steps->_getStepName();
		$method = $this->options->get('method');

		// Before migrate hook
		if ($this->steps->get('first') == true && $this->steps->get('cid') == 0) {
			try
			{
				if (method_exists($this, 'beforeHook')) {
					$this->beforeHook();
				}
			}
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}
		}

		// Get the source data.
		if ($rows === false) {
			$rows = $this->dataSwitch();
		}

		// Call to database method hook
		if ( $method == 'database' OR $method == 'database_all') {
			if (method_exists($this, 'databaseHook')) {
				$rows = $this->databaseHook($rows);
			}
		}

		// Call structure hook to create the db table
		if ($this->steps->get('first') == true && $this->steps->get('cid') == 0) {

			$structureHook = 'structureHook_'.$name;

			if (method_exists($this, $structureHook)) {
				try
				{
					$this->$structureHook();
				}
				catch (Exception $e)
				{
					throw new Exception($e->getMessage());
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
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}
		// If method not exists call the default dataHook
		}else{

			try
			{
				$rows = $this->dataHook($rows);
			}
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}
		}

		// Insert the data to the target
		if ($rows !== false) {

			try
			{
				$this->ready = $this->insertData($rows);
			}
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
			}
		}

		// Load the step object
		$data = $this->steps->load($this->steps->get('name'));

		// Call after migration hook
		if ($this->getTotal() == $this->steps->get('cid')) {
			$this->ready = $this->afterHook($rows);
		}

		// Call after all steps hook
		if ($this->steps->get('name') == $this->steps->get('laststep')
		  && $this->steps->get('cache') == 0
			&& $this->getTotal() == $this->steps->get('cid'))
		{
			$this->ready = $this->afterAllStepsHook();
		}

		return $this->ready;
	}

	/**
	 * dataSwitch
	 *
	 * @return	array	The requested data
	 * @since	3.0.0
	 * @throws	Exception
	 */
	protected function dataSwitch($name = null)
	{
		// Init rows variable
		$rows = array();

		// Get the method and chunk
		$method = $this->options->get('method');
		$chunk = $this->options->get('chunk_limit');

		// TODO: Move this to Drivers
		switch ($method) {
			case 'restful':
				$name = ($name == null) ? $this->steps->_getStepName() : $name;

				$rows = $this->driver->getSourceDataRestList($name, $chunk);
		    break;
			case 'database':
		    $rows = $this->driver->getSourceDatabase();
		    break;
		}

		return $rows;
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
		$table = $this->getDestinationTable();

		// Replacing the table name if xml exists
		$table = $this->replaceTable($table);

		if (is_array($rows)) {

			$total = count($rows);

			foreach ($rows as $row)
			{
				if ($row != false) {
					// Convert the array into an object.
					$row = (object) $row;

					try	{
						//$this->_db->insertObject($table, $row);

						$this->steps->_nextID($total);

					}	catch (Exception $e) {

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
					//$this->_db->insertObject($table, $rows);
				}
				catch (Exception $e)
				{
					throw new Exception($e->getMessage());
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
	public static function getConditionsHook()
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

	/**
 	* Get the table structure
	*/
	public function getTableStructure() {

		// Get the source table
		$table = $this->getSourceTable();

		// Get the structure
		// @@ TODO: move this to Drivers
		if ($this->options->get('method') == 'database') {
			$result = $this->driver->_db_old->getTableCreate($table);
			$structure = str_replace($this->driver->_db_old->getPrefix(), "#__", "{$result[$table]} ;\n\n");
		}else if ($this->options->get('method') == 'rest') {
			$structure = $this->driver->requestRest("tablestructure", str_replace('#__', '', $table));
		}

		// Create only if not exists
		$structure = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $structure);

		// Replacing the table name from xml
		$replaced_table = $this->replaceTable($table);

		if ($replaced_table != $table) {
			$structure = str_replace($table, $replaced_table, $structure);
		}

		// Inserting the structure to new site
		$this->_db->setQuery($structure);
		$this->_db->query();

		return true;
	}

	/**
	 * Replace table name
	 *
	 * @return	string The replaced table
	 * @since 3.0.3
	 * @throws	Exception
	 */
	protected function replaceTable($table, $structure = null) {

		$replaced_table = $table;

		// Replace table name from xml
		$replace = explode("|", $this->steps->get('replace'));

		if (count($replace) > 1) {
			$replaced_table = str_replace($replace[0], $replace[1], $table);
		}

		return $replaced_table;
	}

	/**
	 * @return  string	The destination table key name
	 *
	 * @since   3.0
	 */
	public function getDestKeyName()
	{
		$table = $this->getDestinationTable();

		$query = "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'";
		$this->_db->setQuery( $query );
		$keys = $this->_db->loadObjectList();

		return !empty($keys) ? $keys[0]->Column_name : '';
	}

	/**
	 * @return  bool	Check if the value exists in the table
	 *
	 * @since   3.0
	 */
	public function valueExists($row, $fields)
	{
		$table = $this->getSourceTable();
		$key = $this->getDestKeyName();
		$value = $row->$key;

		$conditions = array();
		foreach ($fields as $field) {
			$conditions[] = "{$field} = {$row->$field}";
		}

		$where = count( $conditions ) ? 'WHERE ' . implode( ' AND ', $conditions ) : '';

		$query = "SELECT `{$key}` FROM {$table} {$where} LIMIT 1";
		$this->_db->setQuery( $query );
		$exists = $this->_db->loadResult();

		return empty($exists) ? false : true;
	}

	/**
	 * TODO: Replace this function: get the new id directly
	 * Internal function to get original database prefix
	 *
	 * @return	an original database prefix
	 * @since	0.5.3
	 * @throws	Exception
	 */
	public function getMapList($table = 'categories', $section = false, $custom = false)
	{
		// Getting the categories id's
		$query = "SELECT *"
		." FROM #__jupgradepro_{$table}";

		if ($section !== false) {
			$query .= " WHERE section = '{$section}'";
		}

		if ($custom !== false) {
			$query .= " WHERE {$custom}";
		}

		$this->_db->setQuery($query);
		$data = $this->_db->loadObjectList('old');

		// Check for query error.
		$error = $this->_db->getErrorMsg();

		if ($error) {
			throw new Exception($error);
			return false;
		}

		return $data;
	}

	/**
	 * Internal function to get original database prefix
	 *
	 * @return	an original database prefix
	 * @since	0.5.3
	 * @throws	Exception
	 */
	public function getMapListValue($table = 'categories', $section = false, $custom = false)
	{
		// Getting the categories id's
		$query = "SELECT new"
		." FROM #__jupgradepro_{$table}";

		if ($section !== false)
		{
			if ($section == 'categories')
			{
				$query .= " WHERE (section REGEXP '^[\-\+]?[[:digit:]]*\.?[[:digit:]]*$' OR section = 'com_section')";
			}
			else
			{
				$query .= " WHERE section = '{$section}'";
			}
		}

		if ($custom !== false) {
			if ($section !== false) {
				$query .= " AND {$custom}";
			}else{
				$query .= " WHERE {$custom}";
			}
		}

		$this->_db->setQuery($query);
		$data = $this->_db->loadResult();

		// Check for query error.
		$error = $this->_db->getErrorMsg();

		if ($error) {
			throw new Exception($error);
			return false;
		}

		return $data;
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
		$query = $this->_db->getQuery(true);
		$query->select('alias');
		$query->from($table);
		if ($extension !== false) {
			$query->where("extension = '{$extension}'");
		}
		$query->where("alias RLIKE '^{$alias}$'", "OR")->where("alias RLIKE '^{$alias}[~]$'");
		$query->order('alias DESC');
		$query->limit(1);
		$this->_db->setQuery($query);

		return (string) $this->_db->loadResult();
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
		return $this->_total;
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getSourceTable()
	{
		return '#__'.$this->steps->get('source');
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getDestinationTable()
	{
		return '#__'.$this->steps->get('destination');
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
			->columns('`message`')
			->values("'{$this->_db->escape($error)}'");
		$this->_db->setQuery($query);
		$this->_db->execute();

		return true;
	}

} // end class