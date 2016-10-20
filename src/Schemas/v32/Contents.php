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

namespace JUpgradeNext\Schemas\v32;

use Joomla\Filter\OutputFilter;
use Joomla\Event\Dispatcher;

use JUpgradeNext\Upgrade\Upgrade;
use JUpgradeNext\Upgrade\UpgradeHelper;
use Joomla\Table\Table;

use stdClass;

/**
 * Upgrade class for content
 *
 * This class takes the content from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Contents extends Upgrade
{
	/**
	* Sets the data in the destination database.
	*
	* @return	void
	* @since	1.0
	* @throws	Exception
	*/
	public function dataHook($rows = null)
	{
		$table	= $this->getDestinationTable();

		// Get category mapping
		$query = "SELECT * FROM #__jupgradepro_categories WHERE section REGEXP '^[\\-\\+]?[[:digit:]]*\\.?[[:digit:]]*$' AND old > 0";
		$this->_db->setQuery($query);
		$catidmap = $this->_db->loadObjectList('old');

		// Find uncategorised category id
		$query = "SELECT id FROM #__categories WHERE extension='com_content' AND path='uncategorised' LIMIT 1";
		$this->_db->setQuery($query);
		$defaultId = $this->_db->loadResult();

		// Initialize values
		$aliases = array();

		$total = count($rows);

		// Insert content data
		foreach ($rows as $row)
		{
			$row = (array) $row;

			// Check if title and alias isn't blank
			$row['title'] = !empty($row['title']) ? $row['title'] : "###BLANK###";
			$row['alias'] = !empty($row['alias']) ? $row['alias'] : "###BLANK###";

			// Add tags if Joomla! is greater than 3.1
			if (version_compare(UpgradeHelper::getVersion($this->container, 'new'), '3.1', '>=')) {
				$row['metadata'] = $row['metadata'] . "\ntags=";
			}

			// Table:store() run an update if id exists into the object so we create them first
			$object = new stdClass();
			$object->id = $row['id'];

			// Inserting the content
			if (!$this->_db->insertObject($table, $object)) {
				throw new Exception($this->_db->getErrorMsg());
			}

			// Get the content table
			$content = Table::getInstance('Content', 'Table', array('dbo' => $this->_db));

			// Bind data to save content
			if (!$content->bind($row)) {
				throw new Exception($content->getError());
			}

			// Check the content
			if (!$content->check()) {
				throw new Exception($content->getError());
			}

			// Insert the content
			if (!$content->store()) {
				throw new Exception($content->getError());
			}

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return false;
	}

	/**
	 * Run custom code after hooks
	 *
	 * @return	void
	 * @since	1.0
	 */
	public function afterHook()
	{
		//$this->fixComponentConfiguration();
	}

	/*
	 * Upgrading the content configuration
	 */
	protected function fixComponentConfiguration()
	{
		if ($this->options->get('method') == 'database') {

			$query = "SELECT params FROM #__components WHERE `option` = 'com_content'";
			$this->driver->_db_old->setQuery($query);
			$articles_config = $this->driver->_db_old->loadResult();

			// Check for query error.
			$error = $this->driver->_db_old->getErrorMsg();

			if ($error) {
				throw new Exception($error);
			}

			// Convert params to JSON
			$articles_config = $this->convertParams($articles_config);

		}else if ($this->options->get('method') == 'restful') {

			$task = "tableparams";
			$table = "components";

			$articles_config = $this->driver->requestRest($task, $table);
		}

		// Update the params on extensions table
		$query = "UPDATE #__extensions SET `params` = '{$articles_config}' WHERE `element` = 'com_content'";
		$this->driver->_db->setQuery($query);
		$this->driver->_db->query();

		// Check for query error.
		$error = $this->driver->_db->getErrorMsg();

		if ($error) {
			throw new Exception($error);
		}
	}

}
