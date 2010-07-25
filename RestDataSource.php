<?
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
require_once 'OmRequest.php';

class RestResponse {
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

	protected $status = self::STATUS_SUCCESS;

	protected $startRow;
	protected $endRow;
	protected $totalRows;

	protected $data = array();
	protected $errors = array();

	function &toArray() {
		$ret = array("status" => $this->status);
		switch ($this->status) {
		case self::STATUS_SUCCESS:
			if (null !== $this->startRow)
				$ret["startRow"] = $this->startRow;
			if (null !== $this->endRow)
				$ret["endRow"] = $this->endRow;
			if (null !== $this->totalRows)
				$ret["totalRows"] = $this->totalRows;
			$ret["data"] =& $this->data;
			return $ret;
		case self::STATUS_FAILURE:
			$ret["data"] =& $this->data;
		default:
			$ret["errors"] =& $this->errors;
		}
		return $ret;
	}

	function addData($data) {
		$this->data[] = $data;
	}

	function jsonEncode() {
		$ret = array();
		$ret["response"] =& $this->toArray();
		return json_encode($ret);
	}

	function xmlEncode() {
		// TODO write this
	}

	function failure($e) {
		$this->status = self::STATUS_FAILURE;
		$this->data = $e->getMessage();
	}
}

abstract class RestRequest extends OmRequest {
	protected $startRow;
	protected $endRow;
	protected $textMatchStyle;
	protected $componentId;
	protected $dataSource;
	protected $oldValues;

	protected $response;

	function decode() {
		/* Data sources must use the postMessage dataProtocol */
		if (!isset($_SERVER["REQUEST_METHOD"]) ||
				$_SERVER["REQUEST_METHOD"] != "POST" ||
				!isset($GLOBALS['HTTP_RAW_POST_DATA']))
			throw new Exception("Unsupported request");

		/* Decode the json encoded parameters from the raw HTTP request */
		$request = (array)json_decode($GLOBALS['HTTP_RAW_POST_DATA']);

		if (!isset($request["operationType"]))
			throw new Exception("Operation type not specified");

		switch ($request["operationType"]) {
		case 'fetch':
			$this->operationType = self::OPERATION_FETCH;
			break;
		case 'add':
			$this->operationType = self::OPERATION_ADD;
			break;
		case 'update':
			$this->operationType = self::OPERATION_UPDATE;
			break;
		case 'remove':
			$this->operationType = self::OPERATION_REMOVE;
			break;
		default:
			throw new Exception("Unknown operation type");
		}

		if (isset($request["startRow"]))
			$this->startRow = (int)$request["startRow"];

		if (isset($request["endRow"]))
			$this->endRow = (int)$request["endRow"];

		if (isset($request["textMatchStyle"]))
			$this->textMatchStyle = $request["textMatchStyle"];

		if (isset($request["oldValues"]))
			$this->oldValues = (array)$request["oldValues"];

		if (isset($request["data"]))
			$this->data = (array)$request["data"];
	}

	function init() {
		parent::init();
		$this->response = new RestResponse();
	}

	function buildFetchCriteria() {
		$c = new Criteria();
		if (null !== $this->startRow) {
			$c->setLimit($this->endRow - $this->startRow);
			$c->setOffset($this->startRow);
		}
		$omFields = $this->arrayToOm($this->data);
		foreach ($omFields as $field => $value) {
			$colName = $this->omPeer->translateFieldName($field, BasePeer::TYPE_FIELDNAME,
					BasePeer::TYPE_COLNAME);
			$c->add($colName, $value);
		}
		return $c;
	}

	function doFetch() {
		$objs = $this->omPeer->doSelect($this->buildFetchCriteria());
		foreach ($objs as $obj)
			$this->response->addData($this->omToArray($obj));
	}

	function doSave() {
		parent::doSave();
		$this->response->addData($this->omToArray($this->om));
	}

	function dispatch() {
		try {
			parent::dispatch();
		} catch (Exception $e) {
			$this->response->failure($e);
		}
	}

	function getResponse() {
		return $this->response;
	}
}

?>
