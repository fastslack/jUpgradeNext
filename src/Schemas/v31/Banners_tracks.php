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

namespace Jupgradenext\Schemas\v31;

use Jupgradenext\Upgrade\Upgrade;

/**
 * Upgrade class for Banners
 *
 * This class takes the banners from the existing site and inserts them into the new site.
 *
 * @since       1.0
 */
class Banners_tracks extends Upgrade
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	array
	 * @since	1.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['where'] = array();

		$conditions['group_by'] = "banner_id";

		return $conditions;
	}
} // end class
