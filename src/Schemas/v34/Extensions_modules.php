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

namespace Jupgradenext\Schemas\v34;

use Jupgradenext\Upgrade\Upgrade;

/**
 * Upgrade class for Weblinks
 *
 * This class takes the weblinks from the existing site and inserts them into the new site.
 *
 * @since	3.0.0
 */
class Extensions_modules extends Upgrade
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	3.0.0
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['as'] = "m";

		$conditions['select'] = '`extension_id` AS eid, `name`, `element`, `type`, `folder`, `client_id`, `ordering`, `params`';

		$where = array();
		$where[] = "m.type = 'module'";
		$where[] = "m.element   NOT   IN   ('mod_mainmenu',   'mod_login',   'mod_popular',   'mod_latest',   'mod_stats',   'mod_unread',   'mod_online',   'mod_toolbar',   'mod_quickicon',   'mod_logged',   'mod_footer',   'mod_menu',   'mod_submenu',   'mod_status',   'mod_title',   'mod_login' )";

		$conditions['where'] = $where;

		//$conditions['group_by'] = 'element';

		return $conditions;
	}
}
