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

namespace Jupgradenext\Schemas\v33;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Upgrade\Upgrade;

/**
 * Upgrade class for Banners
 *
 * This class takes the banners from the existing site and inserts them into the new site.
 *
 * @since       1.0
 */
class Banners extends Upgrade
{
	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since		1.0
	 * @throws	Exception
	 */
	public function &dataHook($rows)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

			// Remove unused fields.
			if (version_compare(UpgradeHelper::getVersion($this->container, 'new'), '2.5', '=')) {
				unset($row['created_by']);
				unset($row['created_by_alias']);
				unset($row['modified']);
				unset($row['modified_by']);
				unset($row['version']);
			}
		}

		return $rows;
	}
}
