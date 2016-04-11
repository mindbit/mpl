<?php
/*
 * Mindbit PHP Library
 * Copyright (C) 2009 Mindbit SRL
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of version 2.1 of the GNU Lesser General Public
 * License as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\SmartClient;

class RPCResponse {
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

	protected $_data = array("status" => self::STATUS_SUCCESS);

	function setStatus($status) {
		$this->_data["status"] = $status;
	}

	function getStatus() {
		return $this->_data["status"];
	}

	function toArray() {
		return $this->_data;
	}

	function jsonEncode() {
		return json_encode($this->toArray());
	}

	function xmlEncode() {
		// TODO write this
	}

	function __set($name, $value) {
		$this->_data[$name] = $value;
	}

	function __get($name) {
		// isset() is fastest because it uses hashing
		if (isset($this->_data[$name]))
			return $this->_data[$name];

		// isset() does not cover null values
		if (array_key_exists($name, $this->_data))
			return $this->_data[$name];

		throw new Exception("Undefined property " . $name);
	}

	function setFailure($msg) {
		$this->setStatus(self::STATUS_FAILURE);
		$this->_data["data"] = $msg;
	}
}
