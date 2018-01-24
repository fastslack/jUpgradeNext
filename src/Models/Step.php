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

namespace Jupgradenext\Models;

use Joomla\Model\AbstractModel;

use Jupgradenext\Steps\Steps;
use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;

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

	  $steps = $this->container->get('steps');
		$steps->load();

		// Check if name exists
		$name = !empty($name) ? $name : $steps->get('name');

		// Check if next step exists
		if (!$steps->getStep($name))
		{
			$this->returnError (404, 'No more steps');
		}

		if (!UpgradeHelper::isCli()) {
			echo $steps->getParameters();
		}else{
			return $steps;
		}
	}

} // end class
