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

namespace Jupgradenext\Schemas\v32;

use Jupgradenext\Upgrade\UpgradeUsers;

/**
 * Upgrade class for the Usergroups
 *
 * This translates the usergroups table from 3.3.
 *
 * @package		MatWare
 * @subpackage	jUpgradeNext
 * @since		3.8.0
 */
class Usergroups extends UpgradeUsers
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	array
	 * @since	  3.6.2
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$options = $container->get('sites')->getSite();

		$conditions = array();
		$conditions['where'] = array();

		if ($options['keep_ids'] == 0)
		{
			$conditions['where'][] = "id > 9";
		}

		$conditions['order'] = "id ASC";

		return $conditions;
	}

	/*
	 * Method to truncate table
	 *
	 * @return	void
	 * @since		3.8.0
	 * @throws	Exception
	 */
	public function truncateTable($run = false)
	{
		if ($this->options['keep_ids'] == 1)
		{
			parent::truncateTable(true);
		}
	}

	/**
	 * Method to do pre-processes modifications before migrate
	 *
	 * @return      boolean Returns true if all is fine, false if not.
	 * @since       3.6.2
	 * @throws      Exception
	 */
	public function beforeHook()
	{
	}

	/**
	 * Sets the data in the destination database.
	 *
	 * @return	void
	 * @since	  3.6.2
	 * @throws	Exception
	 */
	public function dataHook($rows)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (object) $row;

			if ($this->valueExists($row, array('title')))
			{
				$row->title = $row->title ."-".rand(0, 99999999);
			}

			if (!empty($row->user_id) && $this->valueExists($row, array('user_id')))
			{
				$row->user_id = $this->getNewId('#__users', $row->user_id);
			}
		}

		return $rows;
	}
}
