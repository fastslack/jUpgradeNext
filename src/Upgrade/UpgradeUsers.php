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

use Jupgradenext\Upgrade\UpgradeHelper;

/**
 * Database methods
 *
 * This class search for extensions to be migrated
 *
 * @since	3.0.0
 */
class UpgradeUsers extends Upgrade
{
	/**
	 * @var
	 * @since  3.0
	 */
	protected	$usergroup_map = array(
			// Old	=> // New
			0     => 0,	// ROOT
			28		=> 1,	// USERS (=Public)
			29		=> 1,	// Public Frontend
			17		=> 2,	// Registered
			18		=> 2,	// Registered
			19		=> 3,	// Author
			20		=> 4,	// Editor
			21		=> 5,	// Publisher
			30		=> 6,	// Public Backend (=Manager)
			23		=> 6,	// Manager
			24		=> 7,	// Administrator
			25		=> 8,	// Super Administrator
	);

	/**
	 * Get the mapping of the old usergroups to the new usergroup id's.
	 *
	 * @return	array	An array with keys of the old id's and values being the new id's.
	 * @since	1.1.0
	 */
	protected function getUsergroupIdMap()
	{
		return $this->usergroup_map;
	}

	/**
	 * Map old user group from Joomla 1.5 to new installation.
	 *
	 * @return	int	New user group
	 * @since	1.2.2
	 */
	protected function mapUserGroup($id) {
		return isset($this->usergroup_map[$id]) ? $this->usergroup_map[$id] : $id;
	}

	/**
	 * Method to get a map of the User id to ARO id.
	 *
	 * @returns	array	An array of the user id's keyed by ARO id.
	 * @since	0.4.4
	 * @throws	Exception on database error.
	 */
	protected function getUserIdAroMap($aro_id)
	{
		// Get the version
		$old_version = UpgradeHelper::getVersion($this->container, 'external_version');

		// Get thge correct table key
		$key = ($old_version == '1.0') ? 'aro_id' : 'id';

		// Get the data
		$query = $this->container->get('external')->getQuery(true);
		$query->select("u.value");
		$query->from("#__core_acl_aro AS u");
		$query->where("{$key} = {$aro_id}");
		$query->setLimit(1);

		$this->container->get('external')->setQuery( $query );

		// Execute the query
		try {
			$return = $this->container->get('external')->loadResult();
		} catch (RuntimeException $e) {
			throw new RuntimeException($e->getMessage());
		}

		return $return;
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	1.0
	 * @throws	Exception
	 */
	public function &databaseHook($rows)
	{
		// Do some custom post processing on the list.
		foreach ($rows as &$row)
		{
			$row = (array) $row;

      // Chaging admin username and email
      if ($row['username'] == 'admin') {
        $row['username'] = $row['username'].'-old';
        $row['email'] = $row['email'].'-old';
      }
		}

		return $rows;
	}

	/**
	 * Method to do pre-processes modifications before migrate
	 *
	 * @return      boolean Returns true if all is fine, false if not.
	 * @since       3.2.0
	 * @throws      Exception
	 */
	public function beforeHook()
	{
		// Get the data
		$query = $this->_db->getQuery(true);
		$query->select("u.id, u.username");
		$query->from("#__users AS u");
		$query->join("LEFT", "#__user_usergroup_map AS um ON um.user_id = u.id");
		$query->join("LEFT", "#__usergroups AS ug ON ug.id = um.group_id");
		$query->order('u.id ASC');
		$query->setLimit(1);

		$this->_db->setQuery($query);

		try {
			$superuser = $this->_db->loadObject();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		$this->container->set('origin_super_admin', $superuser);
	}

	/*
	 * Fake method after hooks
	 *
	 * @return	void
	 * @since	1.00
	 * @throws	Exception
	 */
	public function afterHook()
	{
	}
}
