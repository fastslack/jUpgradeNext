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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Joomla\CMS\Factory;

/**
 * jUpgradeNext step class
 *
 * @package		jUpgradeNext
 */
class TableBase extends Model
{
  /**
   * The connection name for the model.
   *
   * @var string
   */
  protected $connection = 'default';

  /**
   * Create a new Eloquent model instance.
   *
   * @param  array  $attributes
   * @return void
   */
  public function __construct(array $attributes = [])
  {
    $config = Factory::getConfig();
    $capsule = new Capsule;

    $capsule->addConnection(array(
        'driver'    => 'mysql',
        'host'      => $config->get('host'),
        'database'  => $config->get('db'),
        'username'  => $config->get('user'),
        'password'  => $config->get('password'),
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => $config->get('dbprefix')
    ));

    $capsule->bootEloquent();

    parent::__construct($attributes);
  }
}
