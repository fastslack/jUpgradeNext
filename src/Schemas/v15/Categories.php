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

namespace Jupgradenext\Schemas\v15;

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
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['select'] = '`id`, `id` AS old_id, `title`, `alias`, `section`, `section` AS extension, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`';

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

			if (version_compare(UpgradeHelper::getVersion($this->container, 'origin_version'), '1.0', '>=')) {
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
	public function dataHook($rows)
	{
		// Getting the destination table
		$table = $this->getDestinationTable();

		// Content categories
		$this->section = 'com_content';

		// Get the total
		$total = count($rows);

		// Update the category
		foreach ($rows as $category)
		{
			$category = (array) $category;

			// Fix incorrect dates
			$names = array('created_time', 'checked_out_time', 'modified_time');
			$category = $this->fixIncorrectDate($category, $names);

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

		return false;
	}
}
