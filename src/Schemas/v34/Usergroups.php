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

namespace Jupgradenext\Schemas\v34;

use Jupgradenext\Upgrade\UpgradeUsers;
use Jupgradenext\Models\Cleanup;

/**
 * Upgrade class for the Usergroups
 *
 * This translates the usergroups table from 3.3.
 *
 * @package		MatWare
 * @subpackage	com_jupgradepro
 * @since		3.6.2
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
		$conditions = array();

		$conditions['where'] = array();
		$conditions['where'][] = "id > 9";
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
	public function truncateTable()
	{


		return true;
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
		//$cleanup = new Cleanup;
		//$cleanup->truncateTables(array('#__usergroups'));
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
			$row = (array) $row;

		}

		return $rows;
	}
}