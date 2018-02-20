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

use Joomla\Registry\Registry;
use Joomla\Model\ModelInterface;

/**
 * Joomla Framework Base Model Class
 *
 * @since  1.0
 */
class ModelBase implements ModelInterface
{
	/**
	 * The model state.
	 *
	 * @var    Registry
	 * @since  1.0
	 */
	protected $state;

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry  $state  The model state.
	 *
	 * @since   1.0
	 */
	public function __construct(\Joomla\DI\Container $container = null, Registry $options = null)
	{
		$this->options = ($options instanceof Registry) ? $options : new Registry;
		$this->container = ($container instanceof \Joomla\DI\Container) ? $container : new \Joomla\DI\Container;
	}

	/**
	 * Get the model state.
	 *
	 * @return  Registry  The state object.
	 *
	 * @since   1.0
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set the model state.
	 *
	 * @param   Registry  $state  The state object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setState(Registry $state)
	{
		$this->state = $state;
	}

	/**
	 * returnError
	 *
	 * @return	none
	 * @since	2.5.0
	 */
	public function returnError ($code, $message)
	{
		$response = array();
		$response['code'] = $code;
		$response['message'] = \JText::_($message);
		print(json_encode($response));
		exit;
	}
}
