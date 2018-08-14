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

use Jupgradenext\Upgrade\Upgrade;
use Jupgradenext\Upgrade\UpgradeHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

/**
 * Upgrade class for 3rd party extensions
 *
 * This class search for extensions to be migrated
 *
 * @since	0.4.5
 */
class UpgradeExtensions extends Upgrade
{
	/**
	 * @var
	 * @since  3.0
	 */
	public $xml = null;
  /**
	 * count adapters
	 * @var int
	 * @since	1.1.0
	 */
	public $count = 0;
  /**
	 * @var
	 * @since  3.0
	 */
	protected $extensions = array();


	function __construct(\Joomla\DI\Container $container)
	{
		parent::__construct($container);

    $this->steps = $container->get('steps');

		$name = $this->steps->_getStepName();

		if (!empty($this->steps->xmlpath))
    {
			// Find xml file from jUpgradeNext plugins folder
			$default_xmlfile = JPATH_PLUGINS."/jupgradepro/{$this->steps->xmlpath}";

			if (file_exists($default_xmlfile)) {
				$this->xml = simplexml_load_file($default_xmlfile);
			}
		}
	}

	/**
	 * The public entry point for the class.
	 *
	 * @return	boolean
	 * @since	1.1.0
	 */
	public function upgrade($rows = false)
	{
		try
		{
			// Detect
			if (!$this->detectExtension())
			{
				return false;
			}

			// Migrate
			$return = parent::upgrade();

			$this->migrateExtensionCustom();

		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $return;
	}

	/**
	 * Check if extension migration is supported.
	 *
	 * @return	boolean
	 * @since	1.1.0
	 */
	protected function detectExtension()
	{
		return true;
	}

	/**
	 * Hook to do custom migration after all steps
	 *
	 * @return	boolean Ready
	 * @since	3.0.3
	 */
	protected function afterAllStepsHook()
	{
		return true;
	}

	/**
	 * Get extension version from the Joomla! 1.5 site
	 *
	 * @param	string Relative path to manifest file from Joomla! 1.5 JPATH_ROOT
	 * @return	string Version string
	 * @since	2.5.0
	 */
	protected function getExtensionVersion($manifest)
	{
		if (!file_exists(JPATH_ROOT.'/'.$manifest)) return null;

		$xml = simplexml_load_file(JPATH_ROOT.'/'.$manifest);
		return (string) $xml->version[0];
	}

	/**
	 * Migrate the folders.
	 *
	 * @return	boolean
	 * @since	1.1.0
	 */
	protected function migrateExtensionFolders()
	{
		$params = $this->getParams();

		if (!isset($this->state->folders))
		{
			$this->state->folders = new stdClass();
			$this->state->folders = $this->getCopyFolders();
		}

		while(($value = array_shift($this->state->folders)) !== null)
		{
			//$this->output("{$this->name} {$value}");
			$src = $params->path.DS.$value;
			$dest = JPATH_SITE.DS.$value;
			$copyFolderFunc = 'copyFolder_'.preg_replace('/[^\w\d]/', '_', $value);
			if (method_exists($this, $copyFolderFunc))
			{
				// Use function called like copyFolder_media_kunena (for media/kunena)
				$ready = $this->$copyTableFunc($value);
				if (!$ready) {
					array_unshift($this->state->folders, $value);
				}
			}
			else
			{
				if (JFolder::exists($src) && !JFolder::exists($dest) ) {
					JFolder::copy($src, $dest);
				}
			}
			if ($this->checkTimeout()) {
				break;
			}
		}
		return empty($this->state->folders);
	}

	/**
	 * Fix extensions menu
	 *
	 * @return	boolean Ready
	 * @since	1.1.0
	 */
	protected function fixExtensionMenus()
	{
		// Get component object
		$component = JTable::getInstance ( 'extension', 'JTable', array('dbo'=>$this->_db) );
		$component->load(array('type'=>'component', 'element'=>$this->steps->_getStepName()));

		// First fix all broken menu items
		$query = "UPDATE #__menu SET component_id={$this->_db->quote($component->extension_id)} WHERE type = 'component' AND link LIKE '%option={$this->steps->_getStepName()}%'";
		$this->_db->setQuery ( $query );
		$this->_db->query ();

		return true;
	}

	/**
	 * Migrate custom information.
	 *
	 * @return	boolean Ready
	 * @since	1.1.0
	 */
	protected function migrateExtensionCustom()
	{
		return true;
	}

	/**
	 * A hook to be able to modify params prior as they are converted to JSON.
	 *
	 * @param	object	$object	A reference to the parameters as an object.
	 *
	 * @return	void
	 * @since	1.1.0
	 * @throws	Exception
	 */
	protected function migrateExtensionDataHook()
	{
		// Do customisation of the params field here for specific data.
	}

	/**
	 * Get update site information
	 *
	 * @return	array	Update site information or null
	 * @since	1.1.0
	 */
	protected function getUpdateSite()
  {
		if (empty($this->xml->updateservers->server[0])) {
			return null;
		}

		$server = $this->xml->updateservers->server[0];
		if (empty($server['url'])) {
			return null;
		}

		return array(
			'type'=> ($server['type'] ? $server['type'] : 'extension'),
			'priority'=> ($server['priority'] ? $server['priority'] : 1),
			'name'=> ($server['name'] ? $server['name'] : $this->name),
			'url'=> $server['url']
		);
	}

	/**
	 * Get folders to be migrated.
	 *
	 * @return	array	List of tables without prefix
	 * @since	1.1.0
	 */
	protected function getCopyFolders()
  {
		$folders = !empty($this->xml->folders->folder) ? $this->xml->folders->folder : array();
		$results = array();

		foreach ($folders as $folder)
		{
			$results[] = (string) $folder;
		}

		return $results;
	}

	/**
	 * Get directories to be migrated.
	 *
	 * @return	array	List of directories
	 * @since	1.1.0
	 */
	protected function getCopyTables()
  {
		$tables = !empty($this->xml->tables->table) ? $this->xml->tables->table : array();
		$results = array();

		foreach ($tables as $table)
		{
			$results[] = (string) $table;
		}

		return $results;
	}

	/**
	 * Upgrade the components
	 *
	 * @return
	 * @since	1.1.0
	 * @throws	Exception
	 */
	protected function upgradeComponents()
	{
		// Update
    $container = $this->container;
		$container->get('steps')->load('extensions_components');

		// Get Upgrade instance
		$components = Upgrade::loadInstance($container);

    // Get rows
		$rows = $components->driver->getSourceData();

		$this->_addExtensions ( $rows, 'com' );
    $update = new \stdClass;
    $update->status = 2;
    //$container->get('steps')->updateStep($update);

		return true;
	}

	/**
	 * Upgrade the modules
	 *
	 * @return
	 * @since	1.1.0
	 * @throws	Exception
	 */
	protected function upgradeModules()
	{
    // Update
    $container = $this->container;
		$container->get('steps')->load('extensions_modules');

		// Get Upgrade instance
		$components = Upgrade::loadInstance($container);

    // Get rows
		$rows = $components->driver->getSourceData();

		$this->_addExtensions ( $rows, 'mod' );

    $update = new \stdClass;
    $update->status = 2;
    //$container->get('steps')->updateStep($update);

		return true;
	}

	/**
	 * Upgrade the plugins
	 *
	 * @return
	 * @since	1.1.0
	 * @throws	Exception
	 */
	protected function upgradePlugins()
	{
    // Update
    $container = $this->container;
		$container->get('steps')->load('extensions_plugins');

		// Get Upgrade instance
		$components = Upgrade::loadInstance($container);

    // Get rows
		$rows = $components->driver->getSourceData();

		$this->_addExtensions ( $rows, 'plg' );

    $update = new \stdClass;
    $update->status = 2;
    //$container->get('steps')->updateStep($update);

		return true;
	}

	/**
	 * Upgrade the templates
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	0.4.5
	 * @throws	Exception
	 */
	protected function upgradeTemplates()
	{
		$this->destination = "#__extensions";

		$folders = Folder::folders(JPATH_ROOT.DS.'templates');
		$folders = array_diff($folders, array("system", "beez"));
		sort($folders);

		$rows = array();
		// Do some custom post processing on the list.
		foreach($folders as $folder) {

			$row = array();
			$row['name'] = $folder;
			$row['type'] = 'template';
			$row['element'] = $folder;
			$row['params'] = '';
			$rows[] = $row;
		}

		$this->_addExtensions ( $rows, 'tpl' );
		return true;
	}

	/**
	 * Collect the extensions and set to variable
	 *
	 * @param		array		The extension's list.
	 * @param		string	The extension prefix [com|mod|plg]
	 * @since 1.1.0
	 * @return	none
	 */
	protected function _addExtensions( $rows, $prefix )
	{
		// Create new indexed array
		foreach ($rows as &$row)
		{
			// Convert the array into an object.
			$row = (object) $row;
			$row->id = null;
			$row->extension_id = null;
			$row->element = strtolower($row->element);

			// Ensure that name is always using form: xxx_folder_name
			$name = preg_replace('/^'.$prefix.'_/', '', $row->element);
			if (!empty($row->folder)) {
				$element = preg_replace('/^'.$row->folder.'_/', '', $row->element);
				$row->name = ucfirst($row->folder).' - '.ucfirst($element);
				$name = $row->folder.'_'.$element;
			}
			$name = $prefix .'_'. $name;
			$this->extensions[$name] = $row;
		}
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since 1.1.0
	 * @throws	Exception
	 */
	protected function _processExtensions()
	{
    // Declare return
    $return = array();
    $return['count'] = 0;

		// Get the site parameter
		$site = $this->container->get('sites')->getSite();

		// Get the plugins list
		$query = $this->_db->getQuery(true);
		$query->select('*');
		$query->from('#__extensions');
		$query->where("type = 'plugin'");
		$query->where("folder = 'jupgradepro'");
		$query->where("enabled = 1");
		$query->where("state = 0");

		// Set the query and getting the result
		$this->_db->setQuery($query);
		$plugins = $this->_db->loadObjectList();

		// Get the tables list and prefix from the old site
		if ($site['method'] == "database")
    {
			$old_tables = $this->container->get('external')->getTableList();
      $dbOptions = json_decode($site['database']);
			$old_prefix = $dbOptions->db_prefix;
		}
    else if ($site['method'] == "restful")
    {
			$old_tables = json_decode($this->driver->requestRest('tableslist'));
			$old_prefix = substr($old_tables[10], 0, strpos($old_tables[10], '_')+1);
		}

		// Get the old site version
		$external_ver = UpgradeHelper::getVersion($this->container, 'external_version');
    $external_version = str_replace(".", "", $external_ver);

    if (empty($plugins))
    {
      $return['message'] = '[[g;white;]|] [[gb;red;]✘] No 3rd party extensions plugin installed.';
      $return['code'] = 500;
    }

		// Do some custom post processing on the list.
		foreach ($plugins as $plugin)
		{
			// Looking for xml files
			$files = (array) Folder::files(JPATH_PLUGINS."/jupgradepro/{$plugin->element}/extensions", '\.xml$', true, true);

			foreach ($files as $xmlfile)
			{
				if (!empty($xmlfile)) {

					$element = File::stripExt(basename($xmlfile));

					// Read xml definition file
					$xml = simplexml_load_file($xmlfile);

					$alternative = (string)$xml->alternative;

					if (array_key_exists($alternative, $this->extensions))
					{
						$original = $element;
						$element = $alternative;
					}

					if (array_key_exists($element, $this->extensions))
					{
						$extension = $this->extensions[$element];

						// Getting the php file
						if (!empty($xml->installer->file[0])) {
							$phpfile = JPATH_ROOT.'/'.trim($xml->installer->file[0]);
						}
						if (empty($phpfile)) {
							$default_phpfile = JPATH_PLUGINS."/jupgradepro/{$plugin->element}/extensions/{$element}.php";
							$phpfile = file_exists($default_phpfile) ? $default_phpfile : null;
						}

						// Saving the extensions and migrating the tables
						if ( !empty($phpfile) || !empty($xmlfile) )
            {
							// Adding +1 to count
							$c = $return['count'] = $return['count'] + 1;

              $return['code'] = 200;
              $return['extensions'][$c] = array();
              $return['extensions'][$c]['element'] = $element;
              $return['extensions'][$c]['type'] = isset($extension->type) ? $extension->type : '';
              $return['extensions'][$c]['name'] = $extension->name;

							// Reset the $query object
							$query->clear();

							$xmlpath = "{$plugin->element}/extensions/{$element}.xml";

							// Checking if other migration exists
							$query = $this->_db->getQuery(true);
							$query->select('e.id');
							$query->from('#__jupgradepro_extensions AS e');
							$query->where('e.name = ' . $this->_db->quote($element));
							$query->order("e.id DESC");
							$query->setLimit(1);
							$this->_db->setQuery($query);
							$exists = $this->_db->loadResult();

							if (empty($exists))
							{
								// Inserting the step to #__jupgradepro_extensions table
                $query->clear();
								$columns = "{$this->_db->qn('from')}, {$this->_db->qn('to')}, {$this->_db->qn('name')}, {$this->_db->qn('title')}, {$this->_db->qn('xmlpath')}";
								$query->insert('#__jupgradepro_extensions')->columns($columns);
                $query->values("{$this->_db->q($external_version)}, '99', {$this->_db->q($element)}, {$this->_db->q($xml->name)}, {$this->_db->q($xmlpath)}");

								$this->_db->setQuery($query);
								$this->_db->execute();
							}

							// Inserting the collection if exists
							if (isset($xml->name) && isset($xml->collection))
              {
                $query->clear();
								$columns = "{$this->_db->qn('name')}, {$this->_db->qn('type')}, {$this->_db->qn('location')}, {$this->_db->qn('enabled')}";
								$query->insert('#__update_sites')->columns($columns)
									->values("{$this->_db->q($xml->name)}, 'collection',  {$this->_db->q($xml->collection)}, 1");
								$this->_db->setQuery($query);
								$this->_db->execute();
							}

							// Converting the params
							if (version_compare($external_ver, '1.5', '='))
              {
								$extension->params = $this->convertParams($extension->params);
							}

							// Adding tables to migrate
							if (!empty($xml->tables[0]))
              {
								// Check if tables must to be replaced
								$main_replace = (string) $xml->tables->attributes()->replace;

								$count = count($xml->tables[0]->table);
								$tableExists = false;

								for($i=0;$i<$count;$i++)
                {
									$table = new \stdClass();
									$table->name = $table->source = $table->destination = (string) $xml->tables->table[$i];
									$table->eid = isset($extension->eid) ? $extension->eid : 0;
									$table->element = isset($original) ? $original : $element;
									$table->from = $external_version;
									$table->to = 99;
									$table->replace = (string) $xml->tables->table[$i]->attributes()->replace;
									$table->replace = !empty($table->replace) ? $table->replace : $main_replace;

									$table_name = $old_prefix.$table->name;
									//echo '<pre>',@print_r($table,1),'</pre>';exit;

									if (in_array($table_name, $old_tables))
                  {
										$tableExists = true;

										if (!$this->_db->insertObject('#__jupgradepro_extensions_tables', $table, 'id'))
                    {
											throw new Exception($this->_db->getErrorMsg());
										}
									}
								}

								if ($tableExists == false)
						    {
						      $return['message'] = "No tables found trying to migrate [[gbi;orange;]{$extension->name}] extension";
						      $return['code'] = 500;
						    }
							}

							// Add other extensions from the package
							if (!empty($xml->package[0]))
              {
								foreach ($xml->package[0]->extension as $xml_ext)
                {
									if (isset($this->extensions[(string) $xml_ext->name]))
                  {
										$extension = $this->extensions[(string) $xml_ext->name];
										$state->extensions[] = (string) $xml_ext->name;

										$extension->params = $this->convertParams($extension->params);

										//if (!$this->_db->insertObject('#__extensions', $extension))
                    //{
										//	throw new Exception($this->_db->getErrorMsg());
										//}

										unset ($this->extensions[(string) $xml_ext->name]);
									}
								}
							}

						} //end if

					} else {
						$plgname = (string)$xml->name;
						$return['message'] = "[[g;white;]|] [[gb;orange;]✘] Extension [[gbi;orange;]{$element}] not found. Plugin: [[gbi;orange;]{$plgname}]";
						$return['code'] = 500;
					} // end if

				} // end if

				unset($phpfile);
				unset($xmlfile);

			} // end foreach
		} // end foreach

		return $return;
	}

} // end class
