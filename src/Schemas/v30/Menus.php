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

namespace Jupgradenext\Schemas\v30;

use Joomla\Event\Dispatcher;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Upgrade\UpgradeMenus;
use Joomla\Table\Table;

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
		$root = $container->get('db')->q('root');
		$conditions['where'][] = "m.alias != {$root}";
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

		// Get extensions id's of the new Joomla installation
		$query = $this->_db->getQuery(true);
		$query->select('e.extension_id, e.element');
		$query->from('#__extensions AS e');
		$this->_db->setQuery($query);
		$extensions_ids = $this->_db->loadObjectList('element');

		$total = count($rows);

		foreach ($rows as &$row)
		{
			// Convert the array into an object.
			$row = (array) $row;

			// Fix incorrect dates
			$names = array('checked_out_time');
			$row = $this->fixIncorrectDate($row, $names);

			// Convert the array into an object.
			$row = (object) $row;

			// Getting the duplicated alias
			$alias = $this->getAlias('#__menu', $row->alias);

			// Prevent MySQL duplicate error
			// @@ Duplicate entry for key 'idx_client_id_parent_id_alias_language'
			$row->alias = (!empty($alias)) ? $alias."~" : $row->alias;

			// Get new/old id's values
			$menuMap = new \stdClass();

			// Check if it exists
			$menuMap->old_id = $row->id;
			if (isset($row->id) && $this->valueExists($row, array('id')))
			{
				unset($row->id);
			}

			if (empty($row->language))
			{
				$row->language = "*";
			}

			// Not needed
			//unset($row->id);
			unset($row->name);
			unset($row->option);
			unset($row->componentid);
			unset($row->ordering);

			if ($row->link == 'index.php?option=com_postinstall')
			{
				$row = false;
			}

			if ($this->valueExists($row, array('client_id', 'parent_id', 'alias', 'language')))
			{
				$row = false;
			}
		}

		return $rows;
	}
}
