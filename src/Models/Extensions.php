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
 * jUpgradeNext Model
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
	function extensions() {

		// Get the step
		$steps = new Steps('extensions', true);

		// Get jUpgradeExtensions instance
		$extensions = new Upgrade($this->container);
		$success = $extensions->upgrade();

		if ($success === true) {
			$step->status = 2;
			$step->_updateStep();

			if (!UpgradeHelper::isCli()) {
				print(1);
			}else{
				return true;
			}
		}
	}

	/**
	 * returnError
	 *
	 * @return	none
	 * @since	2.5.0
	 */
	public function returnError ($number, $text)
	{
		$message['number'] = $number;
		$message['text'] = JText::_($text);
		print(json_encode($message));
		exit;
	}

} // end class
