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

namespace Jupgradenext\Schemas\v17;

use Jupgradenext\Upgrade\UpgradeExtensions;

/**
 * Upgrade class for 3rd party extensions
 *
 * This class search for extensions to be migrated
 *
 * @since	0.4.5
 */
class Extensions extends UpgradeExtensions
{


	public function upgrade($rows = false)
	{

		if (!$this->upgradeComponents())
		{
			return false;
		}

		if (!$this->upgradeModules())
		{
			return false;
		}

		if (!$this->upgradePlugins())
		{
			return false;
		}

		try
		{
			$return = $this->_processExtensions();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $return;
	}

} // end class
