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

namespace Jupgradenext\Schemas\v38;

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
			$conditions['where'][] = "id > 5";
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
			foreach ($rows as &$row)
			{
				$row = (object) $row;

				if ($this->valueExists($row, array('title')))
				{
					$row->title = $row->title ."-".rand(0, 99999999);
				}
			}
		}

		return $rows;
	}
}
