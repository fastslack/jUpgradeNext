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

namespace Jupgradenext\Upgrade;

use Jupgradenext\Drivers\Drivers;
use Jupgradenext\Models\Checks;
use Jupgradenext\Steps\Steps;
use Joomla\Database;

/**
 * jUpgradeNext Helper
 *
 * @package  Joomla.Administrator
 * @since    1.0
 */
class UpgradeHelper
{
	private $container;

	/**
	 * Check if the class is called from CLI
	 *
	 * @return  void	True if is running from cli
	 *
	 * @since   1.0
	 */
	public static function isCli()
	{
		return (php_sapi_name() === 'cli') ? true : false;
	}

	/**
	 * Get parameters
	 *
	 * @return  Registry	The parameters Registry object.
	 *
	 * @since   1.0
	 */
	public static function getParams($object = true)
	{
		// Getting the type of interface between web server and PHP
		$sapi = php_sapi_name();

		// Getting the params and Joomla version web and cli
		if ($sapi != 'cli') {
			$params	= \JComponentHelper::getParams('com_jupgradepro');
		}else if ($sapi == 'cli') {
			$params = new \JRegistry(new JConfig);
		}

		return ($object === true) ? $params->toObject() : $params;
	}

	/**
	 * Get the Joomla! version
	 *
	 * @return  string	The Joomla! version
	 *
	 * @since   1.0.0
	 */
	public static function getVersion(\Joomla\DI\Container $container, $site)
	{
		if ($site == 'external_version')
		{
			$checks = new Checks($container);
			return  $checks->checkSite();
		}else if ($site == 'origin_version'){
			return $container->get('origin_version');
		}
	}

	/**
	 * Get the Joomla! version
	 *
	 * @return  string	The Joomla! version
	 *
	 * @since   3.8.0
	 */
	public static function getVersionFromDB($site)
	{
		$db = \JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select($site);
		$query->from("`#__jupgradepro_version`");
		$query->limit(1);
		$db->setQuery($query);

		try {
			return $db->loadResult();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Getting the total
	 *
	 * @return  int	The total number
	 *
	 * @since   1.0
	 */
	public static function getTotal(\Joomla\DI\Container $container)
	{
		$driver = \Jupgradenext\Drivers\Drivers::getInstance($container);

		$total = $driver->getTotal($container->get('steps')->get('source'));

		return $total;
	}

	/**
	 * Populate a sql file
	 *
	 * @return  bool	True if succeful
	 *
	 * @since   3.1.0
	 */
	public static function populateDatabase(& $db, $sqlfile)
	{
		if( !($buffer = file_get_contents($sqlfile)) )
		{
			return -1;
		}

		$queries = $db->splitSql($buffer);

		foreach ($queries as $query)
		{
			$query = trim($query);
			if ($query != '' && $query {0} != '#')
			{
				$db->setQuery($query);
				try {
					$db->query();
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}

		return true;
	}
}
