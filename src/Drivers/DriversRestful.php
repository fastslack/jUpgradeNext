<?php
/**
 * jUpgradeNext
 *
 * @version $Id:
 * @package jUpgradeNext
 * @copyright Copyright (C) 2004 - 2016 Matware. All rights reserved.
 * @author Matias Aguirre
 * @email maguirre@matware.com.ar
 * @link http://www.matware.com.ar/
 * @license GNU General Public License version 2 or later; see LICENSE
 */

namespace Jupgradenext\Drivers;

use Joomla\Http\Http;

use Jupgradenext\Steps\Steps;

/**
 * jUpgradeNext RESTful utility class
 *
 * @package		jUpgradeNext
 */
class DriversRestful extends Drivers
{

	function __construct(\Joomla\DI\Container $container)
	{
		parent::__construct($container);
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since	0.4.4
	 * @throws	Exception
	 */
	public function &getRestData()
	{
		$data = array();

		// Setting the headers for REST
		$rest_username = $this->options->get('restful.username');
		$rest_password = $this->options->get('restful.password');
		$rest_key = $this->options->get('restful.key');

		// Setting the headers for REST
		$str = $rest_username.":".$rest_password;
		$data['Authorization'] = base64_encode($str);

		// Encoding user
		$user_encode = $rest_username.":".$rest_key;
		$data['AUTH_USER'] = base64_encode($user_encode);
		// Sending by other way, some servers not allow AUTH_ values
		$data['USER'] = base64_encode($user_encode);

		// Encoding password
		$pw_encode = $rest_password.":".$rest_key;
		$data['AUTH_PW'] = base64_encode($pw_encode);
		// Sending by other way, some servers not allow AUTH_ values
		$data['PW'] = base64_encode($pw_encode);

		// Encoding key
		$key_encode = $rest_key.":".$rest_key;
		$data['KEY'] = base64_encode($key_encode);

		return $data;
	}

	/**
	 * Get a single row
	 *
	 * @return   step object
	 */
	public function requestRest($task = 'total', $table = false, $chunk = false) {

		// Http instance
		$http = new Http();

		// Get data
		$data = $this->getRestData();

		// Get total
		$data['task'] = $task;
		$data['table'] = ($table !== false) ? $table : '';
		$data['chunk'] = ($chunk !== false) ? $chunk : '';
		$data['keepid'] = $this->options->get('keep_ids') ? $this->options->get('keep_ids') : 0;

		$request = $http->get($this->options->get('restful.hostname').'/index.php', $data);

		$code = $request->code;

		if ($code == 500) {
			throw new Exception($request->body);
		} else {
			return ($code == 200 || $code == 301) ? $request->body : $code;
		}
	}

	/**
	 * Get the raw data for this part of the upgrade.
	 *
	 * @return	array	Returns a reference to the source data array.
	 * @since 3.0.0
	 * @throws	Exception
	 */
	public function getSourceDataRestList($table = null)
	{
		// Declare rows
		$rows = array();

		$chunk = $this->options->get('chunk_limit');

		$rows = $this->requestRest('rows', $table, $chunk);

		return json_decode($rows);
	}

	/**
	 * Get total of the rows of the table
	 *
	 * @access	public
	 * @return	int	The total of rows
	 */
	public function getTotal()
	{
		$total = $this->requestRest('total', $this->_getStepName());

		return (int)$total;
	}

 /**
	* Check if the table exists
 	*
	* @param string $table The table name
	*/
	public function tableExists ($table) {
		return $this->requestRest("tableexists", $table);
	}
}
