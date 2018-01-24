<?php
/**
 * jUpgradeNext
 *
 * @based on https://github.com/eddieajau/joomla-cli-application
 * @copyright  Copyright (C) 2013 New Life in IT Pty Ltd. All rights reserved.
 *
 * @version $Id:
 * @package jUpgradeNext
 * @copyright Copyright (C) 2004 - 2016 Matware. All rights reserved.
 * @author Matias Aguirre
 * @email maguirre@matware.com.ar
 * @link http://www.matware.com.ar/
 * @license GNU General Public License version 2 or later; see LICENSE
 */

namespace Providers;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;
use Jupgradenext\Models\Sites;

/**
 * Registers the Configuration service provider.
 *
 * Note that the application requires the `APPLICATION_CONFIG` constant to be set with the path to the JSON configuration file.
 *
 * @since  1.2
 */
class SitesServiceProvider implements ServiceProviderInterface
{
	/**
	 * @var    string
	 * @since  1.0
	 */
	private $name;

	/**
	 * Class constructor.
	 *
	 * @param   string  $path  The full path and file name for the configuration file.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		//$this->name = $name;
	}

	/**
	 * Registers the service provider within a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		// Workaround for PHP 5.3 compatibility.
		$that = $this;
		$container->share(
			'sites',
			function ($c) use ($that)
			{
				return new Sites($c);
			}
			, true
		);
	}
}
