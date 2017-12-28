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

namespace Jupgradenext\Schemas\v34;

use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;

/**
 * Upgrade class for weblinks
 *
 * This class takes the weblinks from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Weblinks extends Upgrade
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	1.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($options)
	{
		$conditions = array();

		$conditions['select'] = '`id`, `catid`, `title`, `alias`, `url`, `description`, `hits`, '
     .' `state`, `checked_out`, `checked_out_time`, `ordering`, `params`, `language`';

		return $conditions;
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
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			// Convert the array into an object.
			$row = (array) $row;

			if (version_compare(UpgradeHelper::getVersion($this->container, 'new'), '1.0', '<=')) {
				$row['created'] = $row['date'];
				unset($row['approved']);
				unset($row['archived']);
				unset($row['date']);
				unset($row['sid']);
			}

			// Remove unused fields.
			if (version_compare(UpgradeHelper::getVersion($this->container, 'new'), '2.5', '=')) {
				unset($row['version']);
				unset($row['images']);
			}
		}

		return $rows;
	}
}
