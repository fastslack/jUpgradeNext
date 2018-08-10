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

namespace Jupgradenext\Upgrade;

use Joomla\Filter\OutputFilter;
use Joomla\Event\Dispatcher;

use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;
use Joomla\CMS\Table\Content;

use stdClass;

/**
 * Upgrade class for menus
 *
 * This class takes the menus from the existing site and inserts them into the new site.
 *
 * @since	3.8.0
 */
class UpgradeContents extends Upgrade
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
/*
		// Get category mapping
		$query = $this->_db->getQuery(true);
		$query->select('e.*');
		$query->from('#__jupgradepro_old_ids AS e');
		//$query->where("section REGEXP '^[\\-\\+]?[[:digit:]]*\\.?[[:digit:]]*$'");
		$query->andWhere("table = '#__categories'");
		//$query->andWhere("old > 0");

		$this->_db->setQuery($query);
		$catidmap = $this->_db->loadObjectList('old');

		// Find uncategorised category id
		$query = $this->_db->getQuery(true);
		$query->select('e.id');
		$query->from('#__categories AS e');
		$query->where("extension = 'com_content'");
		$query->andWhere("path='uncategorised'");
		$query->setLimit(1);

		$this->_db->setQuery($query);
		$defaultId = $this->_db->loadResult();
*/
		// Initialize values
		//$aliases = array();

		$total = count($rows);

		// Insert content data
		foreach ($rows as $row)
		{
			$row = (array) $row;

			// Fix incorrect dates
			$names = array('created', 'checked_out_time', 'modified', 'publish_up', 'publish_down');
			$row = $this->fixIncorrectDate($row, $names);

			// Check if title and alias isn't blank
			$row['title'] = !empty($row['title']) ? $row['title'] : "###BLANK###";
			$row['alias'] = !empty($row['alias']) ? $row['alias'] : "###BLANK###";


			// Add tags if Joomla! is greater than 3.1
			if (version_compare(UpgradeHelper::getVersion($this->container, 'origin_version'), '3.1', '>=')) {
				$row['metadata'] = $row['metadata'] . "\ntags=";
			}

			// Get section and old id
			$oldlist = new \stdClass();
			$oldlist->old_id = (int) $row['id'];

			if ($this->options['keep_ids'] == 0)
			{
				//unset($row['id']);
			}

/*
			// Table:store() run an update if id exists into the object so we create them first
			$object = new stdClass();
			$object->id = $row['id'];

			// Inserting the content
			try {
				$this->_db->insertObject($table, $object);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
*/
			// Get the content table
			if (version_compare(UpgradeHelper::getVersion($this->container, 'origin_version'), '3.8', '<'))
			{
				$content = Table::getInstance('Content', 'Table', array('dbo' => $this->_db));
			}else{
				$content = new Content($this->_db);
			}

			// Bind data to save content
			try {
				$content->bind($row);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			// Check the content
			try {
				$content->check();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			// Insert the content
			try {
				$content->store();
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}

			// Get new id
			$oldlist->new_id = (int) $content->id;
			$oldlist->table = '#__content';

			// Insert the row backup
			try
			{
				$this->_db->insertObject('#__jupgradepro_old_ids', $oldlist);
			}
			catch (RuntimeException $e)
			{
				throw new RuntimeException($e->getMessage());
			}

			// Updating the steps table
			$this->steps->_nextID($total);
		}

		return $rows;
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
