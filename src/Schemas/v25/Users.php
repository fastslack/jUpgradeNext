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

namespace Jupgradenext\Schemas\v25;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Upgrade\UpgradeUsers;

/**
 * Upgrade class for Users
 *
 * This class takes the users from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Users extends UpgradeUsers
{
	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	1.0
	 * @throws	Exception
	 */
	public function &dataHook($rows)
	{
		// Do some custom post processing on the list.
		if (is_array($rows))
		{
			foreach ($rows as &$row)
			{
				$row = (array) $row;

				// Remove unused fields.
				unset($row['otpKey']);
				unset($row['otep']);
				unset($row['gid']);
				unset($row['usertype']);
			}
		}

		return $rows;
	}
}
