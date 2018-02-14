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
 * @package		jUpgradePro
 */
class Sites extends ModelBase
{
	/**
	 * Initial checks in jUpgradePro
	 *
	 * @return	none
	 * @since	3.8
	 */
	public function getSite($name = null) {

		if (is_null($name))
		{
			$name = $this->container->get('default_site');
		}

    $query = $this->container->get('db')->getQuery(true);

		$query->select('*');
		$query->from("`#__jupgradepro_sites`");
    $query->where("`name` = '{$name}'");
		$this->container->get('db')->setQuery($query);

		$return = $this->container->get('db')->loadAssoc();

		try {
			return $return;
		} catch (RuntimeException $e) {
			throw new RuntimeException($e->getMessage());
		}
	}

	/**
	 * Initial checks in jUpgradePro
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

		$configDbo = array();
		$configDbo['driver'] = 'mysqli';
		$configDbo['host'] = $config['db_hostname'];
		$configDbo['user'] = $config['db_username'];
		$configDbo['password'] = $config['db_password'];
		$configDbo['database'] = $config['db_name'];
		$configDbo['prefix'] = $config['db_prefix'];

		$dbo = DatabaseDriver::getInstance($configDbo);

    return $dbo;
	}

} // end class
