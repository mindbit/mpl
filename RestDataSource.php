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
		if ($this->status == self::STATUS_SUCCESS) {
			if (null !== $this->startRow)
				$ret["startRow"] = $this->startRow;
			if (null !== $this->endRow)
				$ret["endRow"] = $this->endRow;
			if (null !== $this->totalRows)
				$ret["totalRows"] = $this->totalRows;
			$ret["data"] =& $this->data;
		} else {
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
	protected $oldValues;

	protected $response;

	protected $om;
	protected $omPeer;
	protected $omFieldNames;

	function __construct() {
		if (!isset($_REQUEST["_operationType"]))
			throw new Exception("Operation type not specified");
		switch ($_REQUEST["_operationType"]) {
		case 'fetch':
			$this->operationType = self::OPERATION_FETCH;
			break;
		case 'update':
			$this->operationType = self::OPERATION_UPDATE;
			break;
		default:
			throw new Exception("Unknown operation type");
		}

		if (isset($_REQUEST["_startRow"]))
			$this->startRow = (int)$_REQUEST["_startRow"];

		if (isset($_REQUEST["_endRow"]))
			$this->endRow = (int)$_REQUEST["_endRow"];

		if (isset($_REQUEST["_oldValues"]))
			$this->oldValues = (array)json_decode($_REQUEST["_oldValues"]);

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

	function doUpdate() {
		foreach ($this->omFieldNames as $field) {
			if (!isset($_REQUEST[$field]))
				continue;
			$phpName = $this->omPeer->translateFieldName($field, BasePeer::TYPE_FIELDNAME,
					BasePeer::TYPE_PHPNAME);
			call_user_func(array($this->om, "set".$phpName), $_REQUEST[$field]);
		}
		$this->om->setNew(false);
		$this->om->save();

		$this->response->addData($this->omToArray($this->om));
	}

	function dispatch() {
		switch ($this->operationType) {
		case self::OPERATION_FETCH:
			$this->doFetch();
			break;
		case self::OPERATION_UPDATE:
			$this->doUpdate();
			break;
		}
	}

	function getResponse() {
		return $this->response;
	}
}

?>
