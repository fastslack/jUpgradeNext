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
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook()
	{
		$conditions = array();

		$conditions['select'] = '`id`, `id` AS sid, `title`, `alias`, `section`, `section` AS extension, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`';

		$where_or = array();
		$where_or[] = "section REGEXP '^[\\-\\+]?[[:digit:]]*\\.?[[:digit:]]*$'";
		$where_or[] = "section IN ('com_banner', 'com_contact', 'com_contact_details', 'com_content', 'com_newsfeeds', 'com_sections', 'com_weblinks' )";
		$conditions['where_or'] = $where_or;

		$conditions['order'] = "id ASC, section ASC, ordering ASC";

		return $conditions;
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	0.5.6
	 * @throws	Exception
	 */
	public function databaseHook($rows = null)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row['params'] = $this->convertParams($row['params']);
			$row['title'] = str_replace("'", "&#39;", $row['title']);
			$row['description'] = str_replace("'", "&#39;", $row['description']);
			$row['language'] = "*";

			if ($row['extension'] == 'com_banner') {
				$row['extension'] = "com_banners";
			}else if ($row['extension'] == 'com_contact_details') {
				$row['extension'] = "com_contact";
			}

			if (version_compare(UpgradeHelper::getVersion($this->container, 'new'), '1.0', '>=')) {
				$row['created_time'] = '1970-01-01 00:00:00';
				$row['modified_time'] = '1970-01-01 00:00:00';
				$row['checked_out_time'] = '1970-01-01 00:00:00';
				$row['metadesc'] = "";
				$row['metakey'] = "";
				$row['metadata'] = '{"author":"","robots":""}';
			}
		}

		return $rows;
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
		// Getting the destination table
		$table = $this->getDestinationTable();


		//

		// Content categories
		$this->section = 'com_content';


		// Get the total
		$total = count($rows);

		// Table::store() run an update if id exists so we create them first
		if ($this->options->get('keep_ids') == 1)
		{
			$l = 1;

			foreach ($rows as $category)
			{
				$category = (array) $category;

				// Check if id = 1
				if ($category['id'] == 1) {
					continue;
				}else{
					$id = $category['id'];
				}

				$query = $this->_db->getQuery(true);

				//one of the high points of joomla API - colums as array and vaues as comma separated string-...
				// anyway - let's insert some dummy data to prevent children of itself error..
				$columns = array('`id`','`parent_id`','`lft`','`rgt`');
				$values = array($id,1,$l,$l+1);
				$values = implode(',' , $values);

				$query->insert('#__categories')->columns($columns)->values($values);

				try {
					$this->_db->setQuery($query)->execute();
				} catch (RuntimeException $e) {
					throw new RuntimeException($e->getMessage());
				}

				$l = $l + 2 ;
			}
		}

		// Update the category
		foreach ($rows as $category)
		{
			$category = (array) $category;

			// Check if id = 1
			if ($category['id'] == 1) {
				// Set correct values
				$category['root_id'] = 1;
				unset($category['id']);
				unset($category['sid']);
				unset($category['section']);
				// We need an object
				$category = (object) $category;

				try	{
					$this->_db->insertObject('#__jupgradepro_default_categories', $category);
				}	catch (Exception $e) {
					throw new Exception($e->getMessage());
				}

				// Updating the steps table
				$this->steps->_nextID($total);

				continue;
			}

			// Reset some fields
			$category['asset_id'] = $category['lft'] = $category['rgt'] = null;
			// Check if path is correct
			$category['path'] = empty($category['path']) ? $category['alias'] : $category['path'];
			// Fix the access
			$category['access'] = $category['access'] == 0 ? 1 : $category['access'] + 1;
			// Set the correct parent id
			$category['parent_id'] = $category['level'] = 1;

			// Insert the category
			$this->insertCategory($category);

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		$rootcatobj = $this->getFirstCategory();

		// Insert the category id = 1
		if (is_array($rootcatobj) && $this->getTotal() == $this->steps->get('cid'))
		{
			// Check if path is correct
			$rootcatobj['path'] = empty($rootcatobj['path']) ? $rootcatobj['alias'] : $rootcatobj['path'];
			// Fix the access
			$rootcatobj['access'] = $rootcatobj['access'] == 0 ? 1 : $rootcatobj['access'] + 1;
			// Set the correct parent id
			$rootcatobj['parent_id'] = $rootcatobj['level'] = 1;

			// Insert the category
			$this->insertCategory($rootcatobj);

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return false;
	}
}