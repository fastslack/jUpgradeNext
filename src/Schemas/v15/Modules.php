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

namespace Jupgradenext\Schemas\v15;

use Joomla\Registry\Registry;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Upgrade\Upgrade;

use stdClass;

/**
 * Upgrade class for modules
 *
 * This class takes the modules from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Modules extends Upgrade
{
	/**
	 * Setting the conditions hook
	 *
	 * @return	void
	 * @since	1.00
	 * @throws	Exception
	 */
	public static function getConditionsHook($container)
	{
		$conditions = array();

		$conditions['select'] = "`id`, `title`, `content`, `ordering`, `position`,"
			." `checked_out`, `checked_out_time`, `published`, `module`,"
			." `access`, `showtitle`, `params`, `client_id`";

		$conditions['where'][] = "client_id = 0";
		$conditions['where'][] = "module IN ('mod_breadcrumbs', 'mod_footer', 'mod_mainmenu', 'mod_menu', 'mod_related_items', 'mod_stats', 'mod_wrapper', 'mod_archive', 'mod_custom', 'mod_latestnews', 'mod_mostread', 'mod_search', 'mod_syndicate', 'mod_banners', 'mod_feed', 'mod_login', 'mod_newsflash', 'mod_random_image', 'mod_whosonline' )";

		return $conditions;
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
		// Get the JQuery object
		$query = $this->_db->getQuery(true);
		$query->clear();

		$query->update('#__jupgradepro_steps')->set('status = 2')->where('name = \''.$name.'\'');
		try {
			$this->_db->setQuery($query)->execute();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	1.0
	 * @throws	Exception
	 */
	public function databaseHook($rows = null)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

			$row['params'] = $this->convertParams($row['params']);

			## Fix access
			$row['access'] = $row['access']+1;

			## Language
			$row['language'] = "*";

			## Note
			$row['note'] = "";

			## Module field changes
			if ($row['module'] == "mod_mainmenu") {
				$row['module'] = "mod_menu";
			}
			else if ($row['module'] == "mod_archive") {
				$row['module'] = "mod_articles_archive";
			}
			else if ($row['module'] == "mod_latestnews") {
				$row['module'] = "mod_articles_latest";
			}
			else if ($row['module'] == "mod_mostread") {
				$row['module'] = "mod_articles_popular";
			}
			else if ($row['module'] == "mod_newsflash") {
				$row['module'] = "mod_articles_news";
			}
		}

		return $rows;
	}

	/**
	 * Sets the data in the destination database.
	 *
	 * @return	void
	 * @since	1.0
	 * @throws	Exception
	 */
	public function dataHook($rows = null)
	{
		// Getting the source table
		$table = $this->getSourceTable();

		// Set up the mapping table for the old positions to the new positions.
		$map = self::getPositionsMap();
		$map_keys = array_keys($map);

		$total = count($rows);

		//
		foreach ($rows as $row)
		{
			// Convert the array into an object.
			$row = (object) $row;

			## Change positions
			if (isset($this->options['positions']) && $this->options['positions'] == 0) {
				if (in_array($row->position, $map_keys)) {
						$row->position = $map[$row->position];
				}
			}

			// Get old id
			$oldlist = new stdClass();
			$oldlist->old = $row->id;
			unset($row->id);

			if (version_compare(UpgradeHelper::getVersion($this->container, 'origin_version'), '1.5', '>='))
			{
				unset($row->numnews);
				unset($row->iscore);
				unset($row->control);
			}
		}

		return $rows;
	}

	/**
	 * Get the mapping of the old positions to the new positions in the template.
	 *
	 * @return	array	An array with keys of the old names and values being the new names.
	 * @since	0.5.7
	 */
	public static function getPositionsMap()
	{
		$map = array(
			// Old	=> // New
			'search'				=> 'position-0',
			'top'						=> 'position-1',
			'breadcrumbs'		=> 'position-2',
			'left'					=> 'position-7',
			'right'					=> 'position-6',
			'search'				=> 'position-8',
			'footer'				=> 'position-9',
			'header'				=> 'position-15'
		);

		return $map;
	}

	/**
	 * A hook to be able to modify params prior as they are converted to JSON.
	 *
	 * @param	object	$object	A reference to the parameters as an object.
	 *
	 * @return	void
	 * @since	1.0.3
	 * @throws	Exception
	 */
	protected function convertParamsHook(&$object)
	{
		if (isset($object->startLevel)) $object->startLevel++;
	}
}
