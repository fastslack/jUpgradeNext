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

use Jupgradenext\Steps\Steps;
use Jupgradenext\Upgrade\UpgradeExtensions;
use Jupgradenext\Upgrade\UpgradeHelper;

/**
 * jUpgradeNext Extensions Model
 *
 * @package		jUpgradeNext
 */
class Extensions extends ModelBase
{
	/**
	 * Migrate the extensions
	 *
	 * @return	none
	 * @since	2.5.0
	 */
	function extensions()
	{
		// Get the step
		$steps = new Steps($this->container);

		// Get jUpgradeNext Extensions instance
		$extensions = UpgradeExtensions::loadInstance($this->container);

		try
		{
			$success = $extensions->upgrade();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		if ($success['code'] == 200)
		{
			$success['message'] = \JText::_('COM_JUPGRADEPRO_CHECK_EXTENSIONS_DONE');
		}

		if ($success['count'] > 0)
		{
			foreach ($success['extensions'] as $key => $value) {
				$n = "{$value['name']} ({$value['element']})";
				$success['message'] = $success['message'] . \JText::sprintf('COM_JUPGRADEPRO_EXTENSION_FOUND', $n);
			}
		}

		if (!empty($success))
		{
			$update = new \stdClass;
			$update->status = 2;
			//$steps->updateStep($update);

			print(json_encode($success));
		}
	}

} // end class
