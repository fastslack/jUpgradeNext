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

namespace Jupgradenext\Steps;

use Joomla\Registry\Registry;

use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Models\Checks;

/**
 * jUpgradeNext step class
 *
 * @package		jUpgradeNext
 */
class Steps extends Registry
{
	/**
	 * @var
	 * @since  3.0
	 */
	function __construct(\Joomla\DI\Container $container)
	{
		$this->container = $container;

		//$this->options = UpgradeHelper::getParams();

		$this->_db = $this->container->get('db');

		//$extensions = $container->get('config')->get('extensions');
		$extensions = false;

		// Set step table
		if ($extensions == false) {
			$this->_table = '#__jupgradepro_steps';
		}else if($extensions === 'tables') {
			$this->_table = '#__jupgradepro_extensions_tables';
		}else if($extensions == true) {
			$this->_table = '#__jupgradepro_extensions';
		}

		$data = $this->loadFromDb();
		parent::__construct($data);
	}

	/**
	 *
	 * @param   stdClass   $container  Joomla! DI Container
	 *
	 * @return  Steps  A Steps object.
	 *
	 * @since  3.0.0
	 */
	static function loadInstance(\Joomla\DI\Container $container)
	{

		// Create our new jUpgradeNext connector based on the options given.
		try
		{
			$instance = new Steps($container);
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(sprintf('Unable to load Steps object: %s', $e->getMessage()));
		}

		return $instance;
	}

	/**
	 * Get the current step and store it to Registry
	 *
	 * @return   step object
	 */
	public function load($name = null)
	{
		$data = $this->loadFromDb($name);
		$this->bindData($this->data, $data);
	}

	/**
	 * Getting the current step from database and put it into object properties
	 *
	 * @return   step object
	 */
	public function loadFromDb($name = null) {

		$checks = new Checks($this->container);
		$ext_ver = $checks->checkSite();

		// Get the old version
		$orig_ver = $this->container->get('origin_version');

		// Get the data from db
		$query = $this->_db->getQuery(true);
		$query->select('e.*');
		$query->from($this->_table.' AS e');

		if ($this->_table == '#__jupgradepro_extensions_tables') {
			$query->leftJoin('`#__jupgradepro_extensions` AS ext ON ext.name = e.element');
			$query->select('ext.xmlpath');
		}

		if (!empty($name)) {
			$query->where("e.name = '{$name}'");
		}else{
			$query->where("e.status != 2");
		}

		$ext_ver = str_replace(".", "", $ext_ver);
		$orig_ver = str_replace(".", "", $orig_ver);
		//$query->where("{$orig_ver} BETWEEN e.from AND e.to");
		$query->where("{$ext_ver} BETWEEN e.from AND e.to");

		$query->order('e.id ASC');
		$query->limit(1);

		$this->_db->setQuery($query);
		$step = $this->_db->loadAssoc();

		// Check for query error.
		$error = $this->_db->getErrorMsg();
		if (!empty($error)) {
			print_r($error);
			return false;
		}

		// Check if step is an array
		if (!is_array($step)) {
			return false;
		}

		// Reset the $query object
		$query->clear();

		// Select last step
		$query->select('t.name');
		$query->from($this->_table . ' AS t');
		$query->where("t.status = 0");
		if ($this->_table == '#__jupgradepro_extensions_tables') {
			$query->where("element = '{$step['element']}'");
		}

		$query->where("{$ext_ver} BETWEEN t.from AND t.to");

		$query->order('t.id DESC');
		$query->limit(1);

		$this->_db->setQuery($query);

		try {
			$step['laststep'] = $this->_db->loadResult();
		} catch (RuntimeException $e) {
			throw new RuntimeException($e->getMessage());
		}

		return $step;
	}

	/**
	 * Method to get the parameters.
	 *
	 * @return  array  $parameters  The parameters of this object.
	 *
	 * @since   3.0.0
	 */
	public function getParameters()
	{
		return json_encode($this->toArray());
	}

	/**
	 * Get the next step
	 *
	 * @return   step object
	 */
	public function getStep($name = false, $json = true) {

		// Check if step is loaded
		if (empty($name)) {
			return false;
		}

		$update = new \stdClass();

		$site = $this->container->get('sites')->getSite();

		$limit = $update->chunk = $site['chunk_limit'];
		$source = $this->get('source');

		// Get the total
		if (isset($source)) {
			$this->load($source);
			$update->total = UpgradeHelper::getTotal($this->container, $this);
		}

		// We must to fragment the steps
		if ($update->total > $limit) {

			if ($this->get('cache') == 0 && $this->get('status') == 0) {

				if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
					$update->cache = round( ($this->get('total')-1) / $limit, 0, PHP_ROUND_HALF_DOWN);
				}else{
					$update->cache = round( ($this->get('total')-1) / $limit);
				}
				$update->start = 0;
				$update->stop = $limit - 1;
				$update->first = true;
				$update->debug = "{{{1}}}";

			} else if ($this->get('cache') == 1 && $this->get('status') == 1) {

				$update->start = $this->cid;
				$update->cache = 0;
				$update->stop = $this->total - 1;
				$update->debug = "{{{2}}}";
				$update->first = false;

			} else if ($this->get('cache') > 0) {

				$update->start = $this->cid;
				$update->stop = ($this->start - 1) + $limit;
				$update->cache = $this->cache - 1;
				$update->debug = "{{{3}}}";
				$update->first = false;

				if ($this->get('stop') > $this->get('total')) {
					$update->stop = $this->total - 1;
					$update->next = true;
				}else{
					$update->middle = true;
				}
			}

			// Status == 1
			$update->status = 1;

		}else if ($update->total == 0) {

			$update->stop = -1;
			$update->next = 1;
			$update->first = true;
			if ($this->get('name') == $this->get('laststep')) {
				$update->end = true;
			}
			$update->cache = 0;
			$update->status = 2;
			$update->debug = "{{{4}}}";

		}else{

			$update->start = 0;
			$update->first = 1;
			$update->cache = 0;
			$update->status = 1;
			$update->stop = $update->total - 1;
			$update->debug = "{{{5}}}";
		}

		// Mark if is the end of the step
		if ($this->get('name') == $this->get('laststep') && $this->get('cache') == 1) {
			$update->end = true;
		}

		// updating the status flag
		$this->updateStep($update);

		$this->bindData($this->data, $update);

		return json_encode($this->toArray());
	}

	/**
	 * updateStep
	 *
	 * @return	none
	 * @since	2.5.2
	 */
	public function updateStep($update = '')
	{
		$query = $this->_db->getQuery(true);
		$query->update($this->_table);

		$columns = array('status', 'cache', 'total', 'start', 'stop', 'first', 'debug');

		foreach ($columns as $column)
		{
			if (!empty($update->$column)) {
				$query->set("{$column} = '{$update->$column}'");
			}
			elseif (!empty($this->get($column)))
			{
				$value = $this->get($column);
				$query->set("{$column} = '{$value}'");
			}
		}

		$query->where("name = {$this->_db->quote($this->get('name'))}");
		//$query->where("version = {$this->_db->quote($old_ver)}");

		// Execute the query
		$this->_db->setQuery($query)->execute();

		// Check for query error.
		$error = $this->_db->getErrorMsg();

		if ($error) {
			throw new \Exception($error);
		}

		$this->bindData($this->data, $update);

		return true;
	}

	/**
	 *
	 *
	 * @return  boolean  True if the user and pass are authorized
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException
	 */
	public function _updateID($id)
	{
		$checks = new Checks($this->container);
		$ext_ver = $checks->checkSite();

		$ext_ver = str_replace(".", "", $ext_ver);

		$name = $this->_getStepName();

		$query = $this->_db->getQuery(true);
		$query->update($this->_table . ' AS e');
		$query->set("`cid` = '{$id}'");
		$query->where("name = {$this->_db->quote($name)}");
		$query->where("{$ext_ver} BETWEEN e.from AND e.to");
		$query->limit(1);

		// Execute the query
		return $this->_db->setQuery($query)->execute();
	}

	/**
	 * Updating the steps table
	 *
	 * @return  boolean  True if the user and pass are authorized
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException
	 */
	public function _nextID($total = false)
	{
		$update_cid = (int) $this->_getStepID() + 1;
		$this->_updateID($update_cid);
		echo !UpgradeHelper::isCli() ? "" : "•";
	}

	/**
	 * Update the step id
	 *
	 * @return  int  The next id
	 *
	 * @since   3.0.0
	 */
	public function _getStepID()
	{
		$data = $this->loadFromDb($this->get('name'));
		$this->bindData($this->data, $data);
		return $this->get('cid');
	}

	/**
	 * @return  string	The step name
	 *
	 * @since   3.0
	 */
	public function _getStepName()
	{
		return $this->get('name');
	}
}
