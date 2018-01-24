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

namespace Jupgradenext\Schemas\v35;

use Joomla\Event\Dispatcher;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Upgrade\UpgradeMenus;
use Joomla\Table\Table;
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
	 * @since	1.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['as'] = "m";

		$conditions['select'] = 'm.*';

		$conditions['where'] = array();
		$conditions['where'][] = "m.alias != 'root'";
		$conditions['where'][] = "m.id > 101";

		$conditions['order'] = "m.id ASC";

		return $conditions;
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
		$table	= $this->getDestinationTable();

		// Getting the extensions id's of the new Joomla installation
		$query = "SELECT extension_id, element"
		." FROM #__extensions";
		$this->_db->setQuery($query);
		$extensions_ids = $this->_db->loadObjectList('element');

		$total = count($rows);

		foreach ($rows as $row)
		{
			// Convert the array into an object.
			$row = (object) $row;

			// Getting the duplicated alias
			$alias = $this->getAlias('#__menu', $row->alias);

			// Prevent MySQL duplicate error
			// @@ Duplicate entry for key 'idx_client_id_parent_id_alias_language'
			$row->alias = (!empty($alias)) ? $alias."~" : $row->alias;

			// Get new/old id's values
			$menuMap = new stdClass();

			// Save the old id
			$menuMap->old = $row->id;

			// Not needed
			unset($row->id);
			unset($row->name);
			unset($row->option);
			unset($row->componentid);
			unset($row->ordering);

			// Inserting the menu
			try	{
				$this->_db->insertObject($table, $row);
			}	catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			// Save the new id
			$menuMap->new = $this->_db->insertid();

			// Save old and new id
			try	{
				$this->_db->insertObject('#__jupgradepro_menus', $menuMap);
			}	catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return false;
	}
}
