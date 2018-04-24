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

namespace Jupgradenext\Schemas\v40;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Upgrade\UpgradeCategories;

/**
 * Upgrade class for categories
 *
 * This class takes the categories from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Categories extends UpgradeCategories
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	1.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();
		$conditions['select'] = '*';

		// Get the parameters with global settings
		$options = $container->get('sites')->getSite();

		if ($options['keep_ids'] == 1)
		{
			$where_or = array();
			$where_or[] = "extension REGEXP '^[\\-\\+]?[[:digit:]]*\\.?[[:digit:]]*$'";
			$where_or[] = "extension IN ('com_banners', 'com_contact', 'com_content', 'com_newsfeeds', 'com_sections', 'com_weblinks' )";
			$conditions['where_or'] = $where_or;
			$conditions['order'] = "id DESC, extension DESC";
		}else{
			$where = array();
			$where[] = "path != 'uncategorised'";
			$where[] = "(extension REGEXP '^[\-\+]?[[:digit:]]*\.?[[:digit:]]*$' OR extension IN ('com_banners', 'com_contact', 'com_content', 'com_newsfeeds', 'com_sections', 'com_weblinks' ))";
			$conditions['where'] = $where;
			$conditions['order'] = "parent_id DESC";
		}

		return $conditions;
	}

	/**
	 * Sets the data in the destination database.
	 *
	 * @return	void
	 * @since	0.5.6
	 * @throws	Exception
	 */
	public function dataHook($rows = null)
	{
		// Get the database query
		$query = $this->_db->getQuery(true);

		// Get the parameters with global settings
		$options = $this->container->get('sites')->getSite();

		// Get the destination table
		$table = $this->getDestinationTable();

		// Initialize values
		$rootidmap = 0;
		// Content categories
		$this->section = 'com_content';

		// Table::store() run an update if id exists so we create them first
		if ($options['keep_ids'] == 1)
		{
			foreach ($rows as $category)
			{
				$object = new stdClass();

				$category = (array) $category;

				if ($category['id'] == 1) {
					$query->clear();
					$query->select('id+1');
					$query->from('#__categories');
					$query->order('id DESC');
					$query->setLimit(1);
					$this->_db->setQuery($query);
					$rootidmap = $this->_db->loadResult();

					$object->id = $rootidmap;
					$category['old_id'] = $category['id'];
					$category['id'] = $rootidmap;
				}else{
					$object->id = $category['id'];
				}

				// Inserting the categories id's
				try
				{
					$this->_db->insertObject($table, $object);
				}
				catch (Exception $e)
				{
					throw new Exception($this->_db->getErrorMsg());
				}
			}
		}

		$total = count($rows);

		// Update the category
		foreach ($rows as $row)
		{
			$row = (array) $row;

			$row['asset_id'] = null;
			$row['parent_id'] = 1;
			$row['lft'] = null;
			$row['rgt'] = null;
			$row['level'] = null;

			if ($row['id'] == 1) {
				$row['id'] = $rootidmap;
			}

			// Fix incorrect dates
			$names = array('created_time', 'checked_out_time', 'modified_time');
			$row = $this->fixIncorrectDate($row, $names);

			// Update the category data
			$this->insertCategory($row);

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return false;
	}
}
