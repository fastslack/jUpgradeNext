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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Registers the Logger service provider.
 *
 * @since  1.2
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
	/**
	 * Gets a Logger object.
	 *
	 * @param   Container  $c  A DI container.
	 *
	 * @return  Logger
	 *
	 * @since   1.0
	 */
	public function getLogger(Container $c)
	{
		$config = $c->get('config');
		$logger = new Logger($config->get('logger.channel'));

		$logger->pushHandler(new StreamHandler('php://stdout'));

		return $logger;
	}

	/**
	 * Registers the service provider within a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.2
	 */
	public function register(Container $container)
	{
		// Workaround for PHP 5.3 compatibility.
		$that = $this;
		$container->share(
			'logger',
			function(Container $c) use ($that)
			{
				return $that->getLogger($c);
			},
			true
		);
	}
}
