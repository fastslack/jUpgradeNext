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

use JUpgradeNext\Steps\Steps;

/**
 * Registers the Configuration service provider.
 *
 * Note that the application requires the `APPLICATION_CONFIG` constant to be set with the path to the JSON configuration file.
 *
 * @since  1.2
 */
class StepsServiceProvider implements ServiceProviderInterface
{
	/**
	 * Gets a configuration object.
	 *
	 * @param   Container  $c  A DI container.
	 *
	 * @return  Registry
	 *
	 * @since   1.0
	 * @throws  \LogicException if the configuration file does not exist.
	 * @throws  \UnexpectedValueException if the configuration file could not be parsed.
	 */
	public function getSteps(Container $c)
	{
/*
		if (null === $json)
		{
			throw new \UnexpectedValueException('Configuration file could not be parsed.', 500);
		}
*/
		return new Steps($c);
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
			'steps',
			function ($c) use ($that)
			{
				return $that->getSteps($c);
			}
			, true
		);
	}
}
