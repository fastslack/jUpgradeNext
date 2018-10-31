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

namespace Jupgradenext\Models;

use Joomla\Model\AbstractModel;

use Joomla\Database\DatabaseDriver;

/**
 * jUpgradeNext Model
 *
 * @package		jUpgradeNext
 */
class Sites extends ModelBase
{
	/**
	 * Initial checks in jUpgradeNext
	 *
	 * @return	none
	 * @since	3.8
	 */
	public function getSite($name = null) {

		if (is_null($name))
		{
			$name = $this->container->get('default_site');
		}

		$db = $this->container->get('db');
    $query = $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName("#__jupgradepro_sites"));
    $query->where("{$db->quoteName('name')} = {$db->quote($name)}");
		$db->setQuery($query);

		$return = $db->loadAssoc();

		try {
			return $return;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Initial checks in jUpgradeNext
	 *
	 * @return	none
	 * @since	3.8
	 */
	public function getSiteDboConfig($name = null)
	{
		$options = $this->getSite($name);

		return (array) json_decode($options['database']);
	}

  /**
	 * Return the site Dbo
	 *
	 * @return	none
	 * @since	3.8
	 */
	public function getSiteDbo($name) {

    $array = $this->getSite($name);

		if (!$array)
		{
			return false;
		}

    $config = (array) json_decode($array['database']);

		if (empty($config['db_username']) && empty($config['db_password']))
		{
			return false;
		}

		$configDbo = array();
		$configDbo['driver'] = $config['db_type'];
		$configDbo['host'] = $config['db_hostname'];
		$configDbo['user'] = $config['db_username'];
		$configDbo['password'] = $config['db_password'];
		$configDbo['database'] = $config['db_name'];
		$configDbo['prefix'] = $config['db_prefix'];

		$dbo = DatabaseDriver::getInstance($configDbo);

		$dbo->connect();

    return $dbo;
	}

} // end class
