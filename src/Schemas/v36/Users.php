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

namespace Jupgradenext\Schemas\v36;

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
	public function &databaseHook($rows)
	{
		$superuser = $this->container->get('origin_super_admin');

		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

      // Chaging admin username and email
      if ($row['username'] == 'admin') {
        $row['username'] = $row['username'].'-old';
        $row['email'] = $row['email'].'-old';
      }
		}

		return $rows;
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	1.0
	 * @throws	Exception
	 */
	public function dataHook($rows)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

			// Remove unused fields.
			unset($row['otpKey']);
			unset($row['otep']);
			unset($row['gid']);
		}

		return $rows;
	}

}
