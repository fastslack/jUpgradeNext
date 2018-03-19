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

		// Check if sites DB options are correct.
		if ($options['method'] == 'database')
		{
			if ($this->container->get('external')->connected() == false)
			{
				$this->returnError(500, 'COM_JUPGRADEPRO_ERROR_CANNOT_CONNECT_TO_DB');
			}
		}

		// If REST is enable, cleanup the source #__jupgradepro_steps table
		if ($options['method'] == 'restful') {
			// Initialize the driver to check the RESTful connection
			$driver = Drivers::getInstance($this->container);
			$code = $driver->requestRest('cleanup');
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
