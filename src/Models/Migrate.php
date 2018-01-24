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
 * jUpgradeNext Model
 *
 * @package		jUpgradeNext
 */
class Migrate extends ModelBase
{
	/**
	 * Migrate
	 *
	 * @return	none
	 * @since	3.0.3
	 */
	//function migrate($table = false, $json = true, $extensions = false) {
	function migrate(\Jupgradenext\Steps\Steps $steps = null) {

		// Init the jUpgradepro instance
		$jupgrade = new Upgrade($this->container);

		if ($steps === null)
		{
			$steps = $this->container->get('steps');
		}

		//$table = (bool) ($steps->get('table') != false) ? $table : $steps->get('table', null);
		$table = $jupgrade->getSourceTable();

		//@todo fix this in javascript, this is just a workaround
		if ($table == 'undefined') $table = null;

		//$extensions = (bool) ($extensions != false) ? $extensions : $this->container->get('input')->get('extensions', false);

		// Get the database structure
		if ($steps->get('first') == true
			//&& $extensions == 'tables'
			&& $steps->get('cid') == 0) {
			$structure = $jupgrade->getTableStructure();
		}

		// Run the upgrade
		if ($steps->get('total') > 0) {
			try
			{
				$jupgrade->upgrade();
			}
			catch (RuntimeException $e)
			{
				throw new RuntimeException($e->getMessage());
			}
		}

		$update = new \stdClass;

		// Javascript flags
		if ( $steps->get('cid') == $steps->get('stop')+1 && $steps->get('total') != 0) {
			$steps->set('next', true);
		}
		if ($steps->get('name') == $steps->get('laststep')) {
			$steps->set('end', true);
		}

		$empty = false;
		if ($steps->get('cid') == 0 && $steps->get('total') == 0 && $steps->get('start') == 0 && $steps->get('stop') == 0) {
			$empty = true;
		}

		if ($steps->get('stop') == 0) {
			$steps->set('stop', -1);
		}

		// Update #__jupgradepro_steps table if id = last_id
		if ( ($steps->get('total') != 0)
		  && ($empty == false)
			&& ( ($steps->get('total') <= $steps->get('cid')) || ($steps->get('stop') == -1) ) )
		{
			$steps->set('next', true);
			$steps->set('status', 2);

			$update->status = 2;
		}

		// Set chunk
		$site = $this->container->get('sites')->getSite();
		$update->chunk = $site['chunk_limit'];

		$steps->updateStep($update);

		if (UpgradeHelper::isCli()) {
			echo $steps->getParameters();
		}else{
			echo $steps->getParameters();
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
		$message['text'] = \JText::_($text);
		print(json_encode($message));
		exit;
	}
} // end class
