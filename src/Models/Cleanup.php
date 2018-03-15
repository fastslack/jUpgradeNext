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

defined('_JEXEC') or die;

use Joomla\Model\AbstractModel;

use Jupgradenext\Drivers\Drivers;
use Jupgradenext\Steps\Steps;
use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Models\Checks;

/**
 * jUpgradeNext Cleanup Model
 *
 * @package		jUpgradeNext
 */
class Cleanup extends ModelBase
{
	/**
	 * Cleanup
	 *
	 * @return	none
	 * @since	1.2.0
	 */
	function cleanup()
	{
		// Get the parameters with global settings
		$options = $this->container->get('sites')->getSite();

		$skips = (array) json_decode($options['skips']);

		// Check if sites DB options are correct.
		if ($options['method'] == 'database')
		{
			if ($this->container->get('external')->connected() == false)
			{
				$this->returnError(500, 'COM_JUPGRADEPRO_ERROR_CANNOT_CONNECT_TO_DB');
			}
		}

		// Initialise the tables array
		$del_tables = array();

		// If REST is enable, cleanup the source #__jupgradepro_steps table
		if ($options['method'] == 'restful') {
			// Initialize the driver to check the RESTful connection
			$driver = Drivers::getInstance($this->container);
			$code = $driver->requestRest('cleanup');
		}

		// Truncate menu types if menus are enabled
		if ($skips['skip_core_categories'] != 1 && $options['keep_ids'] != 1)
		{
			$del_tables[] = '#__jupgradepro_categories';
			$del_tables[] = '#__jupgradepro_default_categories';
		}

		// Truncate menu types if menus are enabled
		if ($skips['skip_core_menus'] != 1 && $options['keep_ids'] != 1)
		{
			//$del_tables[] = '#__menu_types';
			$del_tables[] = '#__jupgradepro_menus';
		}

		// Truncate contents if are enabled
		if ($skips['skip_core_modules'] != 1)
			$del_tables[] = '#__jupgradepro_modules';

		// Truncate tables
		$this->truncateTables($del_tables);

		$query = $this->container->get('db')->getQuery(true);

		// Insert default root category
		if ($skips['skip_core_categories'] != 1)
		{
			if ($options['keep_ids'] == 1)
			{
				$query->clear();
				$query->delete()->from("#__categories")->where("id > 1");
				try {
					$this->container->get('db')->setQuery($query)->execute();
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}

			$query->clear();
			$query->insert('#__jupgradepro_categories')->columns('`old`, `new`')->values("0, 2");
			try {
				$this->container->get('db')->setQuery($query)->execute();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}

		// Done checks
		if (!UpgradeHelper::isCli())
		{
			$return = array();

			$return['current_version'] = $this->container->get('origin_version');

			$checks = new Checks($this->container);
			$return['ext_version'] = $checks->checkSite();

			$return['method'] = $options['method'];

			return json_encode($return);
		}
	}

	/**
	 * Truncate tables
	 *
	 * @param		array  $del_tables  The list of tables to truncate.
	 *
	 * @return	bool   True if its ok, Exception if not.
	 *
	 * @since	3.8
	 */
	public function truncateTables ($del_tables)
	{
		// Clean selected tables
		for ($i=0;$i<count($del_tables);$i++)
		{
			$query = $this->container->get('db')->getQuery(true);
			$query->delete()->from("{$del_tables[$i]}");

			try {
				$this->container->get('db')->setQuery($query)->execute();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}

		return true;
	}

	/**
	 * Update the status of one step
	 *
	 * @param		string  $name  The name of the table to update
	 *
	 * @return	none
	 *
	 * @since	3.1.1
	 */
	public function updateStep ($name)
	{
		// Get the external version
		$external_version = UpgradeHelper::getVersion($this->container, 'external_version');

		// Get the JQuery object
		$query = $this->container->get('db')->getQuery(true);

		$query->update('#__jupgradepro_steps AS t')->set('t.status = 2')->where('t.name = \''.$name.'\'');
		try {
			$this->container->get('db')->setQuery($query)->execute();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

} // end class
