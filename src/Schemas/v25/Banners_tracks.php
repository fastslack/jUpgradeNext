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
	 * @return	JDatabaseQuery
	 * @since	1.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$query = $container->get('external')->getQuery(true);
		$query->select('*');
		$query->from($container->get('steps')->getSourceTable());

		// @@ TODO: Fix error below
		// SELECT list is not in GROUP BY clause and contains nonaggregated column '#__banner_tracks.track_date'
		// which is not functionally dependent on columns in GROUP BY clause; this is incompatible
		// with sql_mode=only_full_group_by
		$query->group('banner_id');

		return $query;
	}
} // end class
