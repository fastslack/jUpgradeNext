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

namespace JUpgradeNext\Schemas\v10;

use Joomla\Registry\Registry;

use JUpgradeNext\Upgrade\Upgrade;

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
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook()
	{
		$conditions = array();

		$conditions['select'] = '`bid` AS id, `cid`, `type`, `name`, \'\' AS `alias`, `imptotal`, `impmade`, '
													.'`clicks`, `imageurl`, `clickurl`, `date`, `showBanner` AS state, `checked_out`, '
													.'`checked_out_time`, `editor`, `custombannercode`'	;

		$conditions['where'] = array();

		return $conditions;
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return      array   Returns a reference to the source data array.
	 * @since       1.0
	 * @throws      Exception
	 */
	public function databaseHook($rows = null)
	{
		// Getting the categories id's
		$categories = $this->getMapList('categories', 'com_banners');

		// Do some custom post processing on the list.
		foreach ($rows as $index => &$row)
		{
			$row = (array) $row;

			if (!empty($row['params']))
			{
				$row['params'] = $this->convertParams($row['params']);
			}
		}

		return $rows;
	}

	/**
	 * Sets the data in the destination database.
	 *
	 * @return      void
	 * @since       1.0
	 * @throws      Exception
	 */
	public function dataHook($rows = null)
	{

		

		// Fixing the changes between versions
		foreach($rows as &$row)
		{
			$row = (array) $row;

			if (!empty($row['params']))
			{
				$temp = new JRegistry($row['params']);
				$temp->set('imageurl', 'images/banners/' . $row['imageurl']);
				$row['params'] = json_encode($temp->toObject());
			}

			$row['language'] = '*';

			unset($row['imageurl']);
			unset($row['date']);
			unset($row['editor']);
		}

		return $rows;
	}
}
