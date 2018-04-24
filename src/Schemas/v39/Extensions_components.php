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

namespace Jupgradenext\Schemas\v39;

use Jupgradenext\Upgrade\Upgrade;

/**
 * Upgrade class for Weblinks
 *
 * This class takes the weblinks from the existing site and inserts them into the new site.
 *
 * @since	3.0.0
 */
class Extensions_components extends Upgrade
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

			$conditions['as'] = "c";

			$conditions['select'] = '`extension_id` AS eid, `name`, `element`, `type`, `folder`, `client_id`, `ordering`, `params`';

			$where = array();
			$where[] = "c.type = 'component'";
			$where[] = "c.element NOT IN ('com_admin', 'com_banners', 'com_cache', 'com_categories', 'com_checkin', 'com_config', 'com_contact', 'com_content', 'com_cpanel', 'com_frontpage', 'com_installer', 'com_jupgrade', 'com_languages', 'com_login', 'com_mailto', 'com_massmail', 'com_media', 'com_menus', 'com_messages', 'com_modules', 'com_newsfeeds', 'com_plugins', 'com_poll', 'com_search', 'com_sections', 'com_templates', 'com_user', 'com_users', 'com_weblinks', 'com_wrapper' )";

			$conditions['where'] = $where;

			return $conditions;
		}
}
