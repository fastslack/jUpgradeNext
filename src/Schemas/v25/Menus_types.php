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

use Jupgradenext\Upgrade\Upgrade;

/**
 * Upgrade class for menus types
 *
 * This class takes the menus from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Menus_types extends Upgrade
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['select'] = "*";

		$conditions['where'][] = "id != 1";

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
		parent::truncateTable(true);
	}	
}
