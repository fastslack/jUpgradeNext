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

namespace JUpgradeNext\Drivers;


use JUpgradeNext\Schemas\v15;
use JUpgradeNext\Steps\Steps;
use JUpgradeNext\Upgrade\Upgrade;
use JUpgradeNext\Upgrade\UpgradeHelper;

/**
 * jUpgradePro database utility class
 *
 * @package		jUpgradePro
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
	private $extensions_steps = array('extensions', 'ext_components', 'ext_modules', 'ext_plugins');

	function __construct(\Joomla\DI\Container $container)
	{
		parent::__construct($container);

		$this->options = $container->get('config');

		if (null !== $this->_steps)
		{
			$name = !empty($this->_steps->get('name')) ? $this->_steps->get('name') : '';

			// Derive the class name from the driver.
			$class_name = ucfirst(strtolower($name));

			$version = $this->_steps->get('version');
			$version = str_replace(".", "", $version);

			$class = "\\JUpgradeNext\\Schemas\\v{$version}\\{$class_name}";
			$this->getConditionsCallback($class);

			$xmlpath = !empty($this->_steps->get('xmlpath')) ? $this->_steps->get('xmlpath') : '';
		}

		$this->_db_old = $container->get('external');
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getConditionsCallback($class)
	{
		// @@ Fix bug using PHP < 5.2.3 version
		if (version_compare(PHP_VERSION, '5.2.3', '<')) {
			$this->_conditions = call_user_func(array($class, 'getConditionsHook'));
		}else{
			$this->_conditions = $class::getConditionsHook();
		}
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getSourceDatabase( )
	{
		// Get the conditions
		$conditions = $this->getConditionsHook();

		// Process the conditions
		$query = $this->_processQuery($conditions, true);

		// Setting the query
		$chunk_limit = (int) $this->options->get('chunk_limit');
		$cid = (int) $this->_getStepID();
		$this->_db_old->setQuery( $query, $cid, $chunk_limit );

		//echo "\nQUERY: {$query->__toString()}\n";
		$rows = $this->_db_old->loadAssocList();

		try
		{
			$rows = $this->_db_old->loadAssocList();
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

		// Process the conditions
		$query = $this->_processQuery($conditions);

		// Setting the query
		$this->_db_old->setQuery( $query );

		// Get the total
		$total = $this->_db_old->loadResult();

		try
		{
			$total = $this->_db_old->loadAssocList();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		$return = (int) count($total);

		return $return;
	}

	/**
	 * Process the conditions
	 *
	 * @access	public
	 * @return	array	The conditions ready to be added to query
	 * @since  3.1.0
	 */
	public function _processQuery( $conditions, $pagination = false )
	{
		// Create a new query object.
		$query = $this->_db->getQuery(true);

		// Get the SELECT clause
		$select = isset($conditions['select']) ? $conditions['select'] : '*';
		$select = trim(preg_replace('/\s+/', ' ', $select));

		// Get the TABLE and AS clause
		$table = isset($conditions['as']) ? "{$this->getSourceTable()} AS {$conditions['as']}" : $this->getSourceTable();

		// Build the query
		$query->select($select);
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

		if (!empty($key)) {
			$order = isset($conditions['order']) ? $conditions['order'] : "{$key} ASC";
			$query->order($order);
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
		$tables = $this->_db_old->getTableList();

		$table = $this->_db_old->getPrefix().$table;

		return (in_array($table, $tables)) ? 'YES' : 'NO';
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getSourceTable()
	{
		return '#__'.$this->_steps->get('source');
	}

	/**
	 * @return  string	The table name
	 *
	 * @since   3.0
	 */
	public function getDestinationTable()
	{
		return '#__'.$this->_steps->get('destination');
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

			$query = "SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY'";
			$this->_db_old->setQuery( $query );
			$keys = $this->_db_old->loadObjectList();

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
