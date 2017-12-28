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

namespace Jupgradenext\Schemas\v17;

use Jupgradenext\Upgrade\UpgradeUsers;

/**
 * Upgrade class for the Usergroup Map
 *
 * This translates the group mapping table from 1.5 to 1.0
 * Group id's up to 30 need to be mapped to the new group id's.
 * Group id's over 30 can be used as is.
 * User id's are maintained in this upgrade process.
 *
 * @package		jUpgradeNext
 *
 * @since		1.0
 */
class Usergroupmap extends UpgradeUsers
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	array
	 * @since	1.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($options)
	{
		$conditions = array();

		$conditions['where'] = array();
		$conditions['order'] = "user_id ASC";

		return $conditions;
	}

	/**
	 * Method to do pre-processes modifications before migrate
	 *
	 * @return      boolean Returns true if all is fine, false if not.
	 * @since       1.0
	 * @throws      Exception
	 */
	public function beforeHook()
	{
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
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

			if (empty($row['user_id'])) {
				$row = false;
			}
		}

		return $rows;
	}
}
