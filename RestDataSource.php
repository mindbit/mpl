<?

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

abstract class RestRequest {
	const OPERATION_FETCH  = 1;
	const OPERATION_ADD    = 2;
	const OPERATION_UPDATE = 3;
	const OPERATION_REMOVE = 4;

	const PROTECTED_MAGIC = "\000*\000";

	abstract function createOm();

	protected $operationType;
	protected $startRow;
	protected $endRow;
	protected $textMatchStyle;
	protected $componentId;
	protected $dataSource;
	protected $data;
	protected $oldValues;

	protected $response;

	protected $om;
	protected $omPeer;
	protected $omFieldNames;

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

		if (isset($request["oldValues"]))
			$this->oldValues = (array)$request["oldValues"];

		if (isset($request["data"]))
			$this->data = (array)$request["data"];
	}

	function init() {
		$this->response = new RestResponse();

		$this->om = $this->createOm();
		$this->omPeer = $this->om->getPeer();
		$this->omFieldNames = $this->omPeer->getFieldNames(BasePeer::TYPE_FIELDNAME);
	}

	function omToArray($om) {
		$ret = array();
		$om = (array)$om;
		foreach ($this->omFieldNames as $field) {
			$val = $om[self::PROTECTED_MAGIC . $field];
			$ret[$field] = $val === NULL? "" : $val;
		}
		return $ret;
	}

	function arrayToOm() {
		$tableMap = $this->omPeer->getTableMap();
		$ret = array();
		foreach ($this->omFieldNames as $field) {
			if (!isset($this->data[$field]))
				continue;
			$column = $tableMap->getColumn($field);
			$value = $this->data[$field];
			/* For text and numeric columns that can be null we translate "" to NULL. */
			if ($this->data[$field] === "" && ($column->isText() || $column->isNumeric()) &&
					!$column->isNotNull())
				$value = NULL;
			$ret[$field] = $value;
		}
		return $ret;
	}

	function setOmFields($data) {
		foreach ($data as $field => $value) {
			$phpName = $this->omPeer->translateFieldName($field, BasePeer::TYPE_FIELDNAME,
					BasePeer::TYPE_PHPNAME);
			call_user_func(array($this->om, "set".$phpName), $data[$field]);
		}
	}

	function doFetch() {
		$c = new Criteria();
		if (null !== $this->startRow) {
			$c->setLimit($this->endRow - $this->startRow);
			$c->setOffset($this->startRow);
		}
		$objs = $this->omPeer->doSelect($c);
		foreach ($objs as $obj)
			$this->response->addData($this->omToArray($obj));
	}

	function doSave() {
		$this->setOmFields($this->arrayToOm());
		$this->om->save();
		$this->response->addData($this->omToArray($this->om));
	}

	function doRemove() {
		$this->setOmFields($this->arrayToOm());
		$this->om->delete();
	}

	function dispatch() {
		try {
			$this->init();
			$this->decode();

			switch ($this->operationType) {
			case self::OPERATION_FETCH:
				$this->doFetch();
				break;
			case self::OPERATION_UPDATE:
				$this->om->setNew(false);
			case self::OPERATION_ADD:
				$this->doSave();
				break;
			case self::OPERATION_REMOVE:
				$this->doRemove();
				break;
			}
		} catch (Exception $e) {
			$this->response->failure($e);
		}
	}

	function getResponse() {
		return $this->response;
	}
}

?>
