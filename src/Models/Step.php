<?php
/**
 * jUpgradeNext
 *
 * @version $Id:
 * @package jUpgradeNext
 * @copyright Copyright (C) 2004 - 2016 Matware. All rights reserved.
 * @author Matias Aguirre
 * @email maguirre@matware.com.ar
 * @link http://www.matware.com.ar/
 * @license GNU General Public License version 2 or later; see LICENSE
 */

namespace JUpgradeNext\Models;

use Joomla\Model\AbstractModel;

use JUpgradeNext\Steps\Steps;
use JUpgradeNext\Upgrade\Upgrade;
use JUpgradeNext\Upgrade\UpgradeHelper;

/**
 * jUpgradePro Model
 *
 * @package		jUpgradePro
 */
class Step extends ModelBase
{
	/**
	 * Initial checks in jUpgradePro
	 *
	 * @return	none
	 * @since	1.2.0
	 */
	public function step($name = null, $json = true, $extensions = false) {

		// Check if extensions exists if not get it from URI request
		$extensions = (bool) ($extensions != false) ? $extensions : $this->container->get('input')->get('extensions', false);

		// Init step instance
		$steps = new Steps($this->container);
		//$steps = $this->container->get('steps');

		// Check if name exists
		$name = !empty($name) ? $name : $steps->get('name');

		// Get the next step
		if (!$steps->getStep($name))
		{
			return false;
		}

		if (!UpgradeHelper::isCli()) {
			echo $steps->getParameters();
		}else{
			return $steps;
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
		echo json_encode($message);
		exit;
	}

} // end class
