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

use Jupgradenext\Models\Checks;
use Jupgradenext\Steps\Steps;
use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;

/**
 * jUpgradeNext database utility class
 *
 * @package		jUpgradeNext
 */
class DriversDatabase extends Drivers
{
	/**
	 * @var
	 * @since  3.0
	 */
	public $_db_old = null;
	/**
	 * @var	conditions
	 * @since  3.0
	 */
	public $_conditions = null;

	/**
	 * @var    array  List of extensions steps
	 * @since  12.1
	 */
	private $extensions_steps = array('extensions', 'extensions_components', 'extensions_modules', 'extensions_plugins');

	function __construct(\Joomla\DI\Container $container)
	{
		parent::__construct($container);

		$steps = $container->get('steps');

		if (null !== $steps)
		{
			$var = $steps->loadFromDb();

			$name = !empty($steps->get('name')) ? $steps->get('name') : '';

			// Derive the class name from the driver.
			$class_name = ucfirst(strtolower($name));

			$checks = new Checks($container);
			$version = $checks->checkSite();
			$version = str_replace(".", "", $version);

			$class = "\\Jupgradenext\\Schemas\\v{$version}\\{$class_name}";

			if (!class_exists($class))
			{
				$class = "\\Jupgradenext\\Schemas\\v{$version}\\Common";
			}

			$this->getConditionsCallback($class);

			$xmlpath = !empty($steps->get('xmlpath')) ? $steps->get('xmlpath') : '';
		}

		// Creating dabatase instance for this installation
		$this->_db = $container->get('db');
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getConditionsCallback($class)
	{
		$this->_conditions = $class::getConditionsHook($this->container);
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getSourceDatabase( )
	{
		$site = $this->container->get('sites')->getSite();

		$chunk_limit = (int) $site['chunk_limit'];

		// Get the conditions
		$conditions = $this->getConditionsHook();

		// Process the conditions if needed
		if ($conditions instanceof \Joomla\Database\Mysqli\MysqliQuery)
		{
			$query = $conditions;
		} else {
			$query = $this->_processQuery($conditions, true);
		}

		// Setting query
		$cid = (int) $this->_getStepID();
		$query->setLimit($chunk_limit, $cid);
		$this->container->get('external')->setQuery( $query, $cid, $chunk_limit );

		try
		{
			$rows = $this->container->get('external')->loadAssocList();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $rows;
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getTotal()
	{
		// Get the conditions
		$conditions = $this->getConditionsHook();

		// Process the conditions if needed
		if ($conditions instanceof \Joomla\Database\Mysqli\MysqliQuery)
		{
			$query = $conditions;
		} else {
			$query = $this->_processQuery($conditions, false, true);
		}

		// Set query to db instance
		$this->container->get('external')->setQuery( $query );

		// Get the total
		try
		{
			$total = $this->container->get('external')->loadResult();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$return = (int) $total;

		return $return;
	}

	/**
	 * Process the conditions
	 *
	 * @access	public
	 * @return	array	The conditions ready to be added to query
	 * @since  3.1.0
	 */
	public function _processQuery( $conditions, $pagination = false, $total = false )
	{
		// Create a new query object.
		$query = $this->container->get('external')->getQuery(true);

		// Get the SELECT clause
		$select = isset($conditions['select']) ? $conditions['select'] : '*';
		$select = trim(preg_replace('/\s+/', ' ', $select));

		// Get the TABLE and AS clause
		$table = isset($conditions['as']) ? "{$this->getSourceTable()} AS {$conditions['as']}" : $this->getSourceTable();

		// Build the query
		if ($total == true)
		{
			$query->select('COUNT(*)');
		}	else {
			$query->select($select);
		}

		$query->from(trim($table));

		// Set the join[s] into the query
		if (isset($conditions['join'])) {
			$count = count($conditions['join']);

			for ($i=0;$i<$count;$i++) {
				$query->join('LEFT', $conditions['join'][$i]);
			}
		}

		// Set the where[s] into the query
		if (isset($conditions['where'])) {
			$count = count($conditions['where']);

			for ($i=0;$i<$count;$i++) {
				$query->where(trim($conditions['where'][$i]));
			}
		}

		// Set the where[s] into the query
		if (isset($conditions['where_or'])) {
			$count = count($conditions['where_or']);

			for ($i=0;$i<$count;$i++) {
				$query->where(trim($conditions['where_or'][$i]), 'OR');
			}
		}

		// Set the GROUP BY into the query
		if (isset($conditions['group_by'])) {
			$query->group(trim($conditions['group_by']));
		}

		// Process the ORDER clause
		$key = $this->getKeyName();

		if (!empty($key) && $key != false && $total == false) {
			$order = isset($conditions['order']) ? $conditions['order'] : "{$key} ASC";
			$query->order($order);
		}

		// If total is true and group is set, count rows returned
		if ($total == true && isset($conditions['group_by']))
		{
			$q2 = $this->container->get('external')->getQuery(true);
			$q2->select('COUNT(*)');
			$q2->from($query, 'countGroup');

			$query = $q2;
		}

		return $query;
	}

	/*
	 *
	 * @return	void
	 * @since	3.0.0
	 * @throws	Exception
	 */
	public function getConditionsHook()
	{
		return $this->_conditions;
	}

	/**
 	*
	* @param string $table The table name
	*/
	function tableExists ($table) {
		$tables = array();
		$tables = $this->container->get('external')->getTableList();

		$table = $this->container->get('external')->getPrefix().$table;

		return (in_array($table, $tables)) ? 'YES' : 'NO';
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getSourceTable()
	{
		return '#__'.$this->container->get('steps')->get('source');
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getDestinationTable()
	{
		return '#__'.$this->container->get('steps')->get('destination');
	}

	/**
	 * @return  string	The table key name
	 *
	 * @since   3.0
	 */
	public function getKeyName()
	{
		if (empty($this->_table)) {
			$table = $this->getSourceTable();

			if ($table == '#__')
			{
				return false;
			}

			$query = "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'";
			$this->container->get('external')->setQuery( $query );
			$keys = $this->container->get('external')->loadObjectList();

			return !empty($keys) ? $keys[0]->Column_name : '';
		}else{
			return $this->__table;
		}
	}

	/**
	 * Cleanup the data in the destination database.
	 *
	 * @return	void
	 * @since	0.5.1
	 * @throws	Exception
	 */
	protected function cleanDestinationData($table = false)
	{
		// Get the table
		if ($table == false) {
			$table = $this->getDestinationTable();
		}

		if ($this->canDrop) {
			$query = "TRUNCATE TABLE {$table}";
			$this->_db->setQuery($query);
			$this->_db->query();
		} else {
			$query = "DELETE FROM {$table}";
			$this->_db->setQuery($query);
			$this->_db->query();
		}

		// Check for query error.
		$error = $this->_db->getErrorMsg();

		if ($error) {
			throw new Exception($error);
		}
	}
}
