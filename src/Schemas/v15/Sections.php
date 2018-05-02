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

use Jupgradenext\Upgrade\UpgradeCategories;
use Jupgradenext\Upgrade\UpgradeHelper;
use Joomla\CMS\Table\Category;

/**
 * Upgrade class for sections
 *
 * This class takes the sections from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Sections extends UpgradeCategories
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

		$conditions['select'] = '`id` AS old_id, `title`, `alias`, \'com_section\' AS extension, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`';

		$where = array();
		$where[] = "scope = 'content'";

		$conditions['where'] = $where;

		return $conditions;
	}

	/**
	 * Method to do pre-processes modifications before migrate
	 *
	 * @return	boolean	Returns true if all is fine, false if not.
	 * @since	3.2.2
	 * @throws	Exception
	 */
	public function beforeHook()
	{
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	1.00
	 * @throws	Exception
	 */
	public function databaseHook($rows = null)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

			$row['params'] = $this->convertParams($row['params']);
			$row['title'] = str_replace("'", "&#39;", $row['title']);
			$row['description'] = str_replace("'", "&#39;", $row['description']);

			// Fix the access
			$row['access'] = $row['access'] == 0 ? 1 : $row['access'] + 1;

			$row['extension'] = 'com_section';
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
		$total = count($rows);

		// Insert the sections
		foreach ($rows as $row)
		{
			$row = (array) $row;

			// Fix incorrect dates
			$names = array('checked_out_time');
			$row = $this->fixIncorrectDate($row, $names);

			// Inserting the category
			$this->insertCategory($row);

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return false;
	}

	/**
	 * Run custom code after hooks
	 *
	 * @return	void
	 * @since	3.0
	 */
	public function afterHook()
	{
		// Fixing the parents
		$this->fixParents();
		// Insert existing categories
		//$this->insertExisting();
	}

	/**
	 * Update the categories parent's
	 *
	 * @return	void
	 * @since	3.0
	 */
	protected function fixParents()
	{
		$dbType = $this->container->get('config')->get('dbtype');

		if ($dbType == 'postgresql')
		{
			$change_parent = $this->getMapList('#__categories', 0, "section ~ '^[\\-\\+]?[[:digit:]]*\\.?[[:digit:]]*$'");
		}
		else
		{
			$change_parent = $this->getMapList('#__categories', false, "section REGEXP '^[\\-\\+]?[[:digit:]]*\\.?[[:digit:]]*$' AND section != 0");
		}

		// Insert the sections
		foreach ($change_parent as $category)
		{
			// Rebuild the categories table
			if (version_compare(UpgradeHelper::getVersion($this->container, 'origin_version'), '3.8', '<'))
			{
				$table = \Joomla\Table\Table::getInstance('Category', 'Table');
			}else{
				$table = new Category($this->_db);
			}

			$table->load($category->new_id);

			$custom = "{$this->_db->qn('old_id')} = {$this->_db->q($category->section)}";

			$parent = (int) $this->getMapListValue('#__categories', '', $custom);

			if (!empty($parent))
			{
				// Setting the location of the new category
				$table->setLocation($parent, 'last-child');

				// Insert the category
				if (!$table->store()) {
					throw new Exception($table->getError());
				}
			}
		}
	}
} // end class
