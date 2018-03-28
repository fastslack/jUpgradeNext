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

use Joomla\Model\AbstractModel;

use Jupgradenext\Drivers\Drivers;
use Jupgradenext\Steps\Steps;
use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;
use Jupgradenext\Models\Sites;

/**
 * JUpgradeproModelChecks Model
 *
 * @package		jUpgradePro
 */
class Checks extends ModelBase
{
	/**
	 * Instantiate the model.
	 *
	 * @param   Registry  $state  The model state.
	 *
	 * @since   1.0
	 */
	public function __construct(\Joomla\DI\Container $container = null, Registry $options = null)
	{
		parent::__construct($container, $options);

		// Get the parameters with global settings
		$this->options = $this->container->get('sites')->getSite();

		// Initialize the driver to check the RESTful connection
		if ($this->options['method'] == "restful")
		{
			$this->driver = Drivers::getInstance($this->container);
		}
	}

	/**
	 * Initial checks in jUpgradePro
	 *
	 * @return	string  A JSON object with code and message
	 * @since	  1.2.0
	 */
	function checks()
	{
		// Get the origin site Joomla! version
		$origin_version = $this->container->get('origin_version');

		// Define tables array
		$old_columns = array();

		// Check for bad configurations
		if ($this->options['method'] == "restful") {

			$this->optionsRest = (array) json_decode($this->options['restful']);

			if (empty($this->optionsRest['rest_hostname']) || empty($this->optionsRest['rest_username']) || empty($this->optionsRest['rest_password']) || empty($this->optionsRest['rest_key']) ) {
				throw new Exception('COM_JUPGRADEPRO_ERROR_REST_CONFIG');
			}

			if ($this->optionsRest['rest_hostname']== 'http://www.example.org/' || $this->optionsRest['rest_hostname']== '' ||
					$this->optionsRest['rest_username']== '' || $this->optionsRest['rest_password']== '' || $this->optionsRest['rest_key']== '') {
				throw new Exception('COM_JUPGRADEPRO_ERROR_REST_CONFIG');
			}

			// Check if Restful and plugin are fine
			$code = $this->driver->requestRest('check');

			switch ($code) {
				case 401:
					throw new Exception('COM_JUPGRADEPRO_ERROR_REST_501');
				case 402:
					throw new Exception('COM_JUPGRADEPRO_ERROR_REST_502');
				case 403:
					throw new Exception('COM_JUPGRADEPRO_ERROR_REST_503');
				case 405:
					throw new Exception('COM_JUPGRADEPRO_ERROR_REST_505');
				case 406:
					throw new Exception('COM_JUPGRADEPRO_ERROR_REST_506');
			}

			// Get the database parameters
			$this->old_tables = json_decode($this->driver->requestRest('tableslist'));
			$this->old_prefix = substr($this->old_tables[10], 0, strpos($this->old_tables[10], '_')+1);

/*
			// Get component version
			$ext_version = $this->container->get('version');

			// Compare the versions
			if (trim($code) != $ext_version)
			{
				throw new Exception('COM_JUPGRADEPRO_ERROR_VERSION_NOT_MATCH');
			}
*/
		}

		// Check for bad configurations
		if ($this->options['method'] == "database")
		{
			$this->optionsDb = $this->container->get('sites')->getSiteDboConfig();

			if ($this->optionsDb['db_hostname']== '' || $this->optionsDb['db_username']== ''
			  || $this->optionsDb['db_name']== '' || $this->optionsDb['db_prefix']== '' )
			{
				throw new Exception('COM_JUPGRADEPRO_ERROR_DATABASE_CONFIG');
			}

			// Get external driver
			$this->external = $this->container->get('external');

			// Get the database parameters
			$this->old_tables = $this->external->getTableList();
			$this->old_prefix = $this->external->getPrefix();
		}

		// Check the external site Joomla! version
		$external_version = $this->checkSite();

		// Check if the version is fine
		if (empty($external_version) || empty($origin_version)) {
			throw new Exception('COM_JUPGRADEPRO_ERROR_NO_VERSION');
		}

		// Save the versions to database
		$this->setVersion('old', $external_version);
		$this->setVersion('new', $origin_version);

		// Define the message array
		$message = array();
		$message['status'] = "ERROR";

		$query = $this->container->get('db')->getQuery(true);

		// Check safe_mode_gid
		if (@ini_get('safe_mode_gid') && @ini_get('safe_mode')) {
			throw new \Exception('COM_JUPGRADEPRO_ERROR_DISABLE_SAFE_GID');
		}

		// Checking for other migrations
		$query->clear();
		$query->select('cid');
		$query->from($this->container->get('db')->quoteName("#__jupgradepro_steps"));
		$query->where("cid != 0");
		$this->container->get('db')->setQuery($query);
		$latest_migration = $this->container->get('db')->loadObjectList();

		if (count($latest_migration) != 0) {
			$this->returnError (409, 'COM_JUPGRADEPRO_ERROR_LATEST_MIGRATION');
		}else{
			// Set all cid, status and cache to 0
			$query->clear();
			$query->update('#__jupgradepro_steps')->set('cid = 0, status = 0, cache = 0, total = 0, stop = 0, start = 0, first = 0, debug = \'\'');
			$this->container->get('db')->setQuery($query)->execute();

			$query->clear();

			$query->select("{$this->container->get('db')->quoteName('name')}, {$this->container->get('db')->quoteName('to')}, {$this->container->get('db')->quoteName('from')}");
			$query->from($this->container->get('db')->quoteName("#__jupgradepro_steps"));
			$this->container->get('db')->setQuery($query);
			$disableSteps = $this->container->get('db')->loadObjectList();

			$orig_version = (int) str_replace(".", "", $origin_version);
			$ext_version = (int) str_replace(".", "", $external_version);

			// Disable inconpatible steps
			foreach ($disableSteps as $key => $value) {
				if ($value->to != 99)
				{
					if ( $value->to < $orig_version && $value->to > $ext_version )
					{
						$this->updateStep($value->name);
					}
				}
			}
		}

		// Convert the params to array
		$core_skips = json_decode($this->options['skips']);
		$flag = false;

		// Check is all skips is set
		foreach ($core_skips as $k => $v) {
			$core = substr($k, 0, 9);
			$name = substr($k, 10, 18);

			if ($core == "skip_core") {
				if ($v == 0) {
					$flag = true;
				}

				if ($v == 1)
				{
					// Disable the the steps setted by user
					$this->updateStep($name);

					if ($name == 'users')
					{
						// Disable the sections step
						$this->updateStep('arogroup');

						// Disable the sections step
						$this->updateStep('usergroupmap');

						// Disable the sections step
						$this->updateStep('usergroups');

						// Disable the sections step
						$this->updateStep('viewlevels');
					}

					if ($name == 'categories') {
						// Disable the sections step
						$this->updateStep('sections');
					}
				}
			}

			if ($core == "skip_exte") {
				if ($v == 0) {
					$flag = true;
				}
			}
		}

		if ($flag === false) {
			throw new \Exception('COM_JUPGRADEPRO_ERROR_SKIPS_ALL');
		}

		// Checking tables
		if ($core_skips->skip_core_contents != 1 && $this->options['keep_ids'] == 1) {
			$query->clear();
			$query->select('COUNT(id)');
			$query->from($this->container->get('db')->quoteName("#__content"));
			$this->container->get('db')->setQuery($query);
			$content_count = $this->container->get('db')->loadResult();

			if ($content_count > 0) {
				throw new \Exception('COM_JUPGRADEPRO_ERROR_DATABASE_CONTENT');
			}
		}

		// Checking tables
		if ($core_skips->skip_core_users != 1 && $this->options['keep_ids'] == 1) {
			$query->clear();
			$query->select('COUNT(id)');
			$query->from($this->container->get('db')->quoteName("#__users"));
			$this->container->get('db')->setQuery($query);
			$users_count = $this->container->get('db')->loadResult();

			if ($users_count > 1) {
				throw new \Exception('COM_JUPGRADEPRO_ERROR_DATABASE_USERS');
			}
		}

		// Checking tables
		if ($core_skips->skip_core_categories != 1 && $this->options['keep_ids'] == 1) {
			$query->clear();
			$query->select('COUNT(id)');
			$query->from($this->container->get('db')->quoteName("#__categories"));
			$this->container->get('db')->setQuery($query);
			$categories_count = $this->container->get('db')->loadResult();

			if ($categories_count > 7) {
				throw new \Exception('COM_JUPGRADEPRO_ERROR_DATABASE_CATEGORIES');
			}
		}

		// Done checks
		if (!UpgradeHelper::isCli())
			$this->returnError (200, "[[g;white;]|] [[g;orange;]✓] Checks done.");
	}

	/**
	 * Set old site Joomla! version
	 *
	 * @return	none
	 * @since	3.2.0
	 */
	public function setVersion ($site, $version)
	{
		// Set the ols site version
		$query = $this->container->get('db')->getQuery(true);
		$query->update('#__jupgradepro_version')->set("{$site} = '{$version}'");
		$this->container->get('db')->setQuery($query)->execute();
	}

	/**
	 * checkSite
	 *
	 * @return	bool True if sun is shining
	 * @since	  3.8
	 */
	public function checkSite ()
	{
		$version = $this->checkOldVersion();

		if ($version != false)
		{
			return $version;
		}else{
			return false;
		}
	}

	/**
	 * Check the Joomla! version from tables
	 *
	 * @return	version	The Joomla! version
	 * @since	3.2.0
	 */
	public function checkOldVersion ($external = null)
	{
		// Set default
		$version = false;

		if (empty($this->old_tables) && $this->options['method'] == 'restful')
		{
			$this->old_tables = json_decode($this->driver->requestRest('tableslist'));
			$this->old_prefix = substr($this->old_tables[10], 0, strpos($this->old_tables[10], '_')+1);
		}
		else if (empty($this->old_tables) && $this->options['method'] == 'database')
		{
			// Get external driver
			$this->external = $this->container->get('external');

			// Get the database parameters
			$this->old_tables = $this->external->getTableList();
			$this->old_prefix = $this->external->getPrefix();
		}

		// Trim the prefix value
		$prefix = trim($this->old_prefix);

		// Set the tables to search
		$j10 = "{$prefix}bannerfinish";
		$j15 = "{$prefix}core_acl_aro";
		$j25 = "{$prefix}update_categories";
		$j30 = "{$prefix}assets";
		$j31 = "{$prefix}content_types";
		$j32 = $j33 = "{$prefix}postinstall_messages";
		$j34 = "{$prefix}redirect_links";
		$j35 = "{$prefix}utf8_conversion";
		$j36 = "{$prefix}menu_types";
		$j37 = "{$prefix}fields";
		$j38 = "{$prefix}fields_groups";

		// Check the correct version
		if (in_array($j10, $this->old_tables))
		{
			$version = "1.0";
		}
		else if(in_array($j15, $this->old_tables))
		{
			$version = "1.5";
		}
		else if(in_array($j30, $this->old_tables) && !in_array($j25, $this->old_tables) && !in_array($j31, $this->old_tables))
		{
			$version = "3.0";
		}
		else if(in_array($j31, $this->old_tables) && !in_array($j32, $this->old_tables))
		{
			$version = "3.1";
		}
		else if($this->checkColumn($j33, 'requireReset'))
		{
			$version = "3.3";
		}
		else if($this->checkColumn($j34, 'header'))
		{
			$version = "3.4";
		}
		else if(in_array($j35, $this->old_tables))
		{
			$version = "3.5";
		}
		else if(in_array($j32, $this->old_tables))
		{
			$version = "3.2";
		}
		else if($this->checkColumn($j36, 'asset_id'))
		{
			$version = "3.6";
		}
		else if(in_array($j37, $this->old_tables))
		{
			$version = "3.7";
		}
		else if($this->checkColumn($j38, 'params'))
		{
			$version = "3.8";
		}
		else if(in_array($j25, $this->old_tables) || in_array($j30, $this->old_tables))
		{
			$version = "2.5";
		}

		return $version;
	}

	/**
	 * Check if one column exists
	 *
	 * @return	none
	 * @since	3.8.0
	 */
	public function checkColumn ($table, $column)
	{
		if (!in_array($table, $this->old_tables))
		{
			return false;
		}

		if ($this->options['method'] == 'restful')
		{
			$request = $this->driver->requestRest('tablescolumns', $table);

			// Check if Restful and plugin are fine
			$columns = json_decode($request);

			return array_key_exists($column, $columns) ? true : false;
		}
		else if ($this->options['method'] == 'database')
		{
			if ($this->external)
			{
				$columns = $this->external->getTableColumns($table);

				return array_key_exists($column, $columns) ? true : false;
			}else {
				return false;
			}
		}
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
		// Get the external version
		$external_version = UpgradeHelper::getVersion($this->container, 'external_version');

		// Get the JQuery object
		$query = $this->container->get('db')->getQuery(true);

		$query->update('#__jupgradepro_steps')->set('status = 2')->where("name = {$this->container->get('db')->quote($name)}");

		try {
			$this->container->get('db')->setQuery($query)->execute();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

} // end class
