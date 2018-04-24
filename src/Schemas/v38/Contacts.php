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

namespace Jupgradenext\Schemas\v38;

use Jupgradenext\Upgrade\Upgrade;

/**
 * Upgrade class for Contacts
 *
 * This class takes the contacts from the existing site and inserts them into the new site.
 *
 * @since	1.0
 */
class Contacts extends Upgrade
{
  /**
   * Get the raw data for this part of the upgrade.
   *
   * @return	array	Returns a reference to the source data array.
   * @since		1.0
   * @throws	Exception
   */
  public function dataHook($rows)
  {
    // Do some custom post processing on the list.
    foreach ($rows as &$row)
    {
      $row = (array) $row;

      if (empty($row->language))
      {
        $row['language'] = "*";
      }

      // Fix incorrect dates
      $names = array('created', 'checked_out_time', 'modified', 'publish_up', 'publish_down');
      $row = $this->fixIncorrectDate($row, $names);
    }

    return $rows;
  }
}
