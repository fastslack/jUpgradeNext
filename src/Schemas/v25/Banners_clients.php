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
 * Upgrade class for banners clients
 *
 * @package		jUpgradeNext
 *
 * @since		2.5.2
 */
class Banners_clients extends Upgrade
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	  1.6
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		// Get table
		$table = $container->get('steps')->get('source');

		// Create query
		$query = $container->get('db')->getQuery(true);
		$query->select('id, `name`, `state`, `contact`, `email`, `extrainfo`, `checked_out`, `checked_out_time`');
		$query->from($table);

		return $query;
	}
}
