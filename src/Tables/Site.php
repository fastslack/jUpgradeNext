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

namespace Jupgradenext\Tables;

use Jupgradenext\Tables\TableBase;

/**
 * jUpgradeNext site table model
 *
 * @package		jUpgradeNext
 */
class Site extends TableBase
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'jupgradepro_sites';

  /**
   * Indicates if the model should be timestamped.
   *
   * @var bool
   */
  public $timestamps = false;

  /**
	 * Method to save site
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since    3.8
	 */
	public function saveSite($data)
	{
    // Save restful, db and skips as
    $db = $rest = $skip = array();

    foreach ($data as $key => &$value) {
      $tag = explode("_", $key);

      switch ($tag[0]) {
        case 'db':
          $db[$key] = $value;
          unset($data[$key]);
          break;

        case 'rest':
          $rest[$key] = $value;
          unset($data[$key]);
          break;

        case 'skip':
          $skip[$key] = $value;
          unset($data[$key]);
          break;
      }
    }

    $data['database'] = json_encode($db);
    $data['restful'] = json_encode($rest);
    $data['skips'] = json_encode($skip);

    // Unset tags
    unset($data['tags']);

    // Load data to ORM
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }

    if ($this->save())
    {
      return true;
    }

    return false;
	}
}
