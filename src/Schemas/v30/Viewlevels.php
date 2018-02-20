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

namespace Jupgradenext\Schemas\v30;

use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;

/**
 * Upgrade class for weblinks
 *
 * This class takes the weblinks from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Viewlevels extends Upgrade
{
	/**
	 * Sets the data in the destination database.
	 *
	 * @return	void
	 * @since	1.0
	 * @throws	Exception
	 */
	public function dataHook($rows = null)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			// Convert the array into an object.
			$row = (array) $row;

			//if (version_compare(UpgradeHelper::getVersion($this->container, 'external_version'), '1.0', '<=')) {

			//}
		}

		return $rows;
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
		$cleanup = new Cleanup($this->container);
		$cleanup->truncateTables(array($this->getDestinationTable()));

		return true;
	}
}
