<?
/*	Mindbit PHP Library
 *	Copyright (C) 2009 Mindbit SRL
 *
 *	This library is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU Lesser General Public
 *	License as published by the Free Software Foundation; either
 *	version 2.1 of the License, or (at your option) any later version.
 *
 *	This library is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *	Lesser General Public License for more details.
 *
 *	You should have received a copy of the GNU Lesser General Public
 *	License along with this library; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

class SmartClientRPCResponse {
	const STATUS_SUCCESS = 0;

	const STATUS_FAILURE = -1;

	const STATUS_VALIDATION_ERROR = -4;

	const STATUS_LOGIN_INCORRECT = -5;

	const STATUS_MAX_LOGIN_ATTEMPTS_EXCEEDED = -6;

	const STATUS_LOGIN_REQUIRED = -7;

	const STATUS_LOGIN_SUCCESS = -8;

	const STATUS_TRANSPORT_ERROR = -90;

	const STATUS_UNKNOWN_HOST_ERROR = -91;

	const STATUS_CONNECTION_RESET_ERROR = -92;

	const STATUS_SERVER_TIMEOUT = -100;

	protected $data = array();

	function setStatus($status) {
		$this->status = $status;
	}

	function toArray() {
		return $this->data;
	}

	function jsonEncode() {
		return json_encode($this->toArray());
	}

	function xmlEncode() {
		// TODO write this
	}

	function __set($name, $value) {
		$this->data[$name] = $value;
	}

	function __get($name) {
		// isset() is fastest because it uses hashing
		if (isset($this->data[$name]))
			return $this->data[$name];

		// isset() does not cover null values
		if (array_key_exists($name, $this->data))
			return $this->data[$name];

		throw new Exception("Undefined property " . $name);
	}
}
?>
