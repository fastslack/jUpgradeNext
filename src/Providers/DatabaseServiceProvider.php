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

namespace Providers;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseDriver;

class DatabaseServiceProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->share(
			"Joomla\\Database\\DatabaseDriver",
			function () use ($container)
			{
				$config = $container->get("config")->toArray();
				return DatabaseDriver::getInstance($config["database"]);
			},
			true
		);

		/**
		 * Until we release Joomla DI with alias support.
		 */
		$container->set(
			"db",
			function () use ($container)
			{
				return $container->get("Joomla\\Database\\DatabaseDriver");
			}
		);
	}
}
