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

	/*
	 * Clone table structure from source table to destination table
	 *
	 * @return	bool  True if success.
	 * @since		3.0.0
	 * @throws	Exception
	 */
	public function cloneTableStructure() {

		$dbType = $this->container->get('config')->get('dbtype');

		// Get the source table
		$table = $this->container->get('steps')->getSourceTable();

		// Get site params
		$site = $this->container->get('sites')->getSite();

		// Get table structure;
		$structure = $this->getStructure($table);

		// Create only if not exists
		$structure = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $structure);

		if ($dbType == 'postgresql')
		{
			$structure = $this->transformToPostgreStructure($structure);
		}

		// Replacing the table name from xml
		$replaced_table = $this->replaceTable($table);

		if ($replaced_table != $table) {
			$structure = str_replace($table, $replaced_table, $structure);
		}

		// Inserting the structure to new site
		try {
			$this->_db->setQuery($structure)->execute();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		return true;
	}

	/**
	 * Transform MySQL table structure to PostgreSQL structure
	 *
	 * @return  string  The MySQL structure of the table
	 *
	 * @since   3.8.0
	 */
	protected function transformToPostgreStructure($structure)
	{
		// Remove double quote
		$pattern = array('/\'\'/i');
		$replacement = array("");
		$structure = preg_replace($pattern, $replacement, $structure);

		// Remove comments
		$pattern = array('/COMMENT (["\'])([^"\']+)\1/i');
		$replacement = array("");
		$structure = preg_replace($pattern, $replacement, $structure);

		// Replace quotes
		$structure = str_replace("`", "\"", $structure);

		// Set serial
		$pattern = array('/int\((\d+)\) unsigned NOT NULL AUTO_INCREMENT,/i', '/int\((\d+)\) unsigned NOT NULL,/i');
		$replacement = array("serial NOT NULL,", "serial NOT NULL,");
		$structure = preg_replace($pattern, $replacement, $structure);

		$structure = str_replace("NOT NULL AUTO_INCREMENT", "NOT NULL", $structure);

		// Types
		$pattern = array('/int\((\d+)\)/i', '/year\((\d+)\)/i');
		$replacement = array('integer', 'smallint');
		$structure = preg_replace($pattern, $replacement, $structure);

		$pattern = array('/tinyinteger /i', '/biginteger /i', '/double /i');
		$replacement = array('smallint ', 'bigint ', 'decimal ');
		$structure = preg_replace($pattern, $replacement, $structure);

		$pattern = array('/varchar\((\d+)\)/i');
		$replacement = array('character varying($1)');
		$structure = preg_replace($pattern, $replacement, $structure);

		// DateTime
		$pattern = array('/datetime NOT NULL DEFAULT CURRENT_TIMESTAMP/', '/datetime DEFAULT NULL/', '/datetime NOT NULL/');
		$r = "datetime DEFAULT '1970-01-01 00:00:00'::timestamp without time zone NULL";
		$replacement = array($r, $r, $r);
		$structure = preg_replace($pattern, $replacement, $structure);

		$pattern = array('/datetime DEFAULT/i', '/datetime NOT NULL/');
		$r = "timestamp without time zone DEFAULT";
		$replacement = array($r, $r);
		$structure = preg_replace($pattern, $replacement, $structure);

		// Serial NOT NULL
		$pattern = array('/integer\((\d+)\) NOT NULL AUTO_INCREMENT,/i');
		$replacement = array("serial NOT NULL,");
		$structure = preg_replace($pattern, $replacement, $structure);

		// Remove engine, autoincrement and charset
		$pattern = array('/\) ENGINE=(\w+) AUTO_INCREMENT=(\d+) DEFAULT CHARSET=(\w+) ;/i', '/\) ENGINE=(\w+) DEFAULT CHARSET=(\w+) ;/i');
		$replacement = array(') ;', ') ;');
		$structure = preg_replace($pattern, $replacement, $structure);

		// Remove keys
		// @@ TODO: fix transform keys
		$pattern = array( '/UNIQUE KEY "(["\'])([^"\']+)" \("(["\'])([^"\']+)"\)/',
											'/KEY (["\'])([^"\']+)\" \((.*?)\),/i',
											'/KEY (["\'])([^"\']+)\" \((.*?)\)\)/i',
											'/KEY (["\'])([^"\']+)\" \((["\'])([^"\']+)\"\)/i');
		$replacement = array('');
		$structure = preg_replace($pattern, $replacement, $structure);

		// Fix primary key
		$pattern = array('/PRIMARY KEY \("(\w+)"\),/');
		$replacement = array('PRIMARY KEY ("$1")');
		$structure = preg_replace($pattern, $replacement, $structure);

		return $structure;
	}

	/**
	 * Replace table name
	 *
	 * @return	string The replaced table
	 * @since 	3.0.3
	 * @throws	Exception
	 */
	public function replaceTable($table, $structure = null) {

		$replaced_table = $table;

		// Replace table name from xml
		$replace = explode("|", $this->container->get('steps')->get('replace'));

		if (count($replace) > 1) {
			$replaced_table = str_replace($replace[0], $replace[1], $table);
		}

		return $replaced_table;
	}

	/**
	 * Get PostgreSQL next serial value
	 *
	 * @param	string	$table	The table to search.
	 *
	 * @return	none
	 * @since		3.8.0
	 * @throws	Exception
	 */
	protected function getNextVal()
	{
		// Get variables
		$dbType = $this->container->get('config')->get('dbtype');
		$table = $this->getDestinationTable();

		// Replacing the table name from xml
		$replaced_table = $this->driver->replaceTable($table);

		if ($replaced_table != $table) {
			$table = str_replace($table, $replaced_table, $table);
		}

		if ($dbType == 'postgresql' && $this->relation != false)
		{
			$query = "select nextval('{$table}_id_seq')";
			$this->_db->setQuery($query)->execute();
		}
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
