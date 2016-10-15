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

namespace JUpgradeNext\Schemas\v15;

use Joomla\Event\Dispatcher;

use JUpgradeNext\Upgrade\UpgradeHelper;
use JUpgradeNext\Upgrade\UpgradeMenus;
use JUpgradeNext\Table\Table;
use stdClass;

/**
 * Upgrade class for Menus
 *
 * This class takes the menus from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Menus extends UpgradeMenus
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	array
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook()
	{
		$conditions = array();

		$conditions['as'] = "m";

		$conditions['select'] = 'm.*, c.option, p.alias AS palias';

		$join = array();
		$join[] = "#__components AS c ON c.id = m.componentid";
		$join[] = "#__menu AS p ON p.id = m.parent";

		$conditions['where'] = array();
		$conditions['join'] = $join;

		$conditions['order'] = "m.id ASC";

		return $conditions;
	}

	/**
	 * A hook to be able to modify params prior as they are converted to JSON.
	 *
	 * @param	object	$object	A reference to the parameters as an object.
	 *
	 * @return	void
	 * @since	1.0
	 * @throws	Exception
	 */
	protected function convertParamsHook(&$object)
	{
		if (isset($object->menu_image)) {
			if((string)$object->menu_image == '-1'){
				$object->menu_image = '';
			}
		}

		$object->show_page_heading = (isset($object->show_page_title) && !empty($object->page_title)) ? $object->show_page_title : 0;
	}

	/**
	 * Sets the data in the destination database.
	 *
	 * @return	void
	 * @since	1.0
	 * @throws	Exception
	 */
	public function dataHook($rows = null)
	{
		// Get the query
		$query = $this->_db->getQuery(true);

		// Get the table name
		$tablename = $this->getDestinationTable();

		// Getting the extensions id's of the new Joomla installation
		$query->clear();
		$query->select('extension_id, element');
		$query->from('#__extensions');
		$this->_db->setQuery($query);
		$extensions_ids = $this->_db->loadObjectList('element');

		// Initialize values
		$unique_alias_suffix = 1;

		$total = count($rows);

		$oldnewmap = array();

		// Create a dispatcher.
		$dispatcher = new Dispatcher;

		$config = array();
		$config['dbo'] = $this->_db;
		$config['dispatcher'] = $dispatcher;

		// Getting the table and query
		$table = Table::getInstance('Menu', 'Table', $config);

		// Start the update
		foreach ($rows as &$row)
		{
			// Convert the array into an object.
			$row = (object) $row;

			// Converting params to JSON
			$row->params = $this->convertParams($row->params);

			// Fixing language
			$row->language = '*';

			// Fixing access
			$row->access++;

			// Fixing level
			if (isset($row->sublevel))
			{
				$row->level = $row->sublevel++;
			}

			// Prevent MySQL duplicate error
			// @@ Duplicate entry for key 'idx_client_id_parent_id_alias_language'
			$alias = $this->getAlias('#__menu', $row->alias);
			$row->alias = (!empty($alias)) ? $alias."-".rand(0, 999999) : $row->alias;

			// Fixing menus URLs
			$row = $this->migrateLink($row);

			// Get new/old id's values
			$rowMap = new stdClass();

			// Save the old id
			$rowMap->old = $row->id;

			// Fixing id if == 1 (used by root)
			if ($row->id == 1) {
				$query->clear();
				$query->select('id + 1');
				$query->from('#__menu');
				$query->order('id DESC');
				$query->limit(1);
				$this->_db->setQuery($query);
				$row->id = $this->_db->loadResult();
			}

			// Fixing extension_id
			if (isset($row->option)) {
				$row->component_id = isset($extensions_ids[$row->option]) ? $extensions_ids[$row->option]->extension_id : 0;
			}

			// Fixing name
			$row->title = $row->name;

			if (version_compare(UpgradeHelper::getVersion($this->container, 'new'), '1.0', '>='))
				unset($row->ordering);

			// Not needed
			unset($row->id);
			unset($row->name);
			unset($row->option);
			unset($row->componentid);

			// Bind the data
			try {
				$table->bind((array) $row);
			} catch (RuntimeException $e) {
				throw new RuntimeException($e->getMessage());
			}

			// Store to database
			try {
				$table->store();
			} catch (RuntimeException $e) {
				throw new RuntimeException($e->getMessage());
			}

			// Save the new id in rowmap and row
			$rowMap->new 	= $table->id;
			$row->id 		= $table->id;

			$oldnewmap[$rowMap->old] = $rowMap;

			// Save old and new id
			try	{
				$this->_db->insertObject('#__jupgradepro_menus', $rowMap);
			}	catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			$table->reset();
			$table->id = 0;
		}

		// now rebuild tree by fixing parents and location

		foreach ($rows AS $row)
		{
			$table->reset();
			$table->id = 0;

			$row->lft = $row->rgt = null;

			$row->parent = isset($oldnewmap[$row->parent]) ? $oldnewmap[$row->parent]->new : 1;

			// Bind the data
			try {
				$table->bind((array) $row);
			} catch (RuntimeException $e) {
				throw new RuntimeException($e->getMessage());
			}

			$table->setLocation($row->parent, 'last-child');

			try {
				$table->check();
			} catch (RuntimeException $e) {
				throw new RuntimeException($e->getMessage());
			}

			// Store to database
			try {
				$table->store();
			} catch (RuntimeException $e) {
				throw new RuntimeException($e->getMessage());
			}

			$table->rebuildPath($table->id);


			// Updating the steps table
			$this->steps->_nextID($total);

		}

		// rebuild table
		try {
			$table->rebuild();
		} catch (RuntimeException $e) {
			throw new RuntimeException($e->getMessage());
		}

		return false;
	}

	/*
	 * Fake method after hooks
	 *
	 * @return	void
	 * @since	1.00
	 * @throws	Exception
	 */
	public function afterHook()
	{
		if ($this->params->keep_ids == 1)
		{
			$this->insertDefaultMenus();
		}
	}

	/**
	 * Insert the default menus deleted on cleanups to maintain the original id's
	 *
	 * @return	void
	 * @since	0.5.2
	 * @throws	Exception
	 */
	public function insertDefaultMenus()
	{
		jimport('joomla.table.table');

		// Getting the database instance
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		// Getting the table and query
		$table = Table::getInstance('Menu', 'Table');

		// Getting the data
		$query->select('`menutype`, `title`, `alias`, `note`, `path`, `link`, `type`, `published`, `parent_id`, `component_id`, `ordering`, `checked_out`, `checked_out_time`, `browserNav`, `access`, `img`, `template_style_id`, `params`, `home`, `language`, `client_id`');
		$query->from('#__jupgradepro_default_menus');
		$query->order('id ASC');
		$db->setQuery($query);
		$menus = $db->loadAssocList();

		foreach ($menus as $menu) {

			// Unset id
			$menu['id'] = 0;

			// Getting the duplicated alias
			$alias = $this->getAlias('#__menu', $menu['alias']);

			// Prevent MySQL duplicate error
			// @@ Duplicate entry for key 'idx_client_id_parent_id_alias_language'
			$menu['alias'] = (!empty($alias)) ? $alias."~" : $menu['alias'];

			// Looking for parent
			$parent = 1;
			$explode = explode("/", $menu['path']);

			if (count($explode) > 1) {

				$query->clear();
				$query->select('id');
				$query->from('#__menu');
				$query->where("path = '{$explode[0]}'");
				$query->order('id ASC');
				$query->limit(1);

				$db->setQuery($query);
				$parent = $db->loadResult();
			}

			// Resetting the table object
			$table->reset();
			// Setting the location of the new category
			$table->setLocation($parent, 'last-child');
			// Bind the data
			$table->bind($menu);
			// Store to database
			$table->store();
		}

		$table->reset();

		if (!$table->rebuild()) {
			echo JError::raiseError(500, $table->getError());
		}
	}
}