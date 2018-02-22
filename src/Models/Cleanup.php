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

		// Get the db instance
		$this->_db = $this->container->get('db');

		// Initialise the tables array
		$del_tables = array();

		// If REST is enable, cleanup the source #__jupgradepro_steps table
		if ($options['method'] == 'restful') {
			// Initialize the driver to check the RESTful connection
			$driver = Drivers::getInstance($this->container);
			$code = $driver->requestRest('cleanup');
		}

		// Skiping the steps setted by user
		foreach ($skips as $k => $v) {
			$core = substr($k, 0, 9);
			$name = substr($k, 10, 18);

			if ($core == "skip_core") {
				if ($v == 1) {

					// Disable the the steps setted by user
					$this->updateStep($name);

					if ($name == 'users') {
						// Disable the sections step
						$this->updateStep('arogroup');

						// Disable the sections step
						$this->updateStep('usergroupmap');

						// Disable the sections step
						$this->updateStep('usergroups');

						// Disable the sections step
						$this->updateStep('viewlevels');
					}

					if ($name == 'categories') {
						// Disable the sections step
						$this->updateStep('sections');
					}
				}
			}

			if ($k == 'skip_extensions') {
				if ($v == 1) {
					// Disable the extensions step
					$this->updateStep('extensions');
				}
				else if ($v == 0)
				{
					// Add the tables to truncate for extensions
					$del_tables[] = '#__jupgradepro_extensions_tables';
				}
			}
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

		// Truncate contents if are enabled
		if ($skips['skip_core_contents'] != 1 && $options['keep_ids'] != 1)
			//$del_tables[] = '#__content';

		// Truncate usergroups if are enabled
		if ($skips['skip_core_users'] != 1 && $options['keep_ids'] != 1)
		{
			//$del_tables[] = '#__usergroups';
			//$del_tables[] = '#__viewlevels';
		}

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
					$this->_db->setQuery($query)->execute();
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}

			$query->clear();
			$query->insert('#__jupgradepro_categories')->columns('`old`, `new`')->values("0, 2");
			try {
				$this->_db->setQuery($query)->execute();
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
		if (empty($this->_db))
		{
			$this->_db = $this->container->get('db');
		}

		// Clean selected tables
		for ($i=0;$i<count($del_tables);$i++)
		{
			$query = $this->_db->getQuery(true);
			$query->delete()->from("{$del_tables[$i]}");

			try {
				$this->_db->setQuery($query)->execute();
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
		$query = $this->_db->getQuery(true);

		$query->update('#__jupgradepro_steps AS t')->set('t.status = 2')->where('t.name = \''.$name.'\'');
		try {
			$this->_db->setQuery($query)->execute();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

} // end class
