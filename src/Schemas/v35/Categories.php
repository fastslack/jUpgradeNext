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

namespace JUpgradeNext\Schemas\v35;

use JUpgradeNext\Upgrade\UpgradeHelper;
use JUpgradeNext\Upgrade\UpgradeCategories;

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
	public static function getConditionsHook()
	{
		// Get the component parameters
		JLoader::import('helpers.jupgradepro', JPATH_COMPONENT_ADMINISTRATOR);
		$params = UpgradeHelper::getParams();

		$conditions = array();
		$conditions['select'] = '*';

		if ($this->options->get('keep_ids') == 1)
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
		// Get the destination table
		$table = $this->getDestinationTable();
		// Get the component parameter with global settings
		
		// Initialize values
		$rootidmap = 0;
		// Content categories
		$this->section = 'com_content';

		// Table::store() run an update if id exists so we create them first
		if ($this->params->keep_ids == 1)
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
					$query->limit(1);
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
				catch (RuntimeException $e)
				{
					throw new RuntimeException($this->_db->getErrorMsg());
				}
			}
		}

		$total = count($rows);

		// Update the category
		foreach ($rows as $category)
		{
			$category = (array) $category;

			$category['asset_id'] = null;
			$category['parent_id'] = 1;
			$category['lft'] = null;
			$category['rgt'] = null;
			$category['level'] = null;

			if ($category['id'] == 1) {
				$category['id'] = $rootidmap;
			}

			// Update the category data
			$this->insertCategory($category);

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return false;
	}
}
