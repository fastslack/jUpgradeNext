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

namespace JUpgradeNext\Schemas\v15;

use JUpgradeNext\Upgrade\Upgrade;

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
	 * @return	array
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook($options)
	{
		$conditions = array();

		$conditions['as'] = "m";

		$conditions['select'] = 'm.menutype, m.menutype AS title';

		$conditions['group_by'] = "m.menutype";

		$conditions['where'] = array();
		$conditions['where'][] = "m.menutype != 'mainmenu'";

		$conditions['order'] = "m.menutype ASC";

		return $conditions;
	}
}
