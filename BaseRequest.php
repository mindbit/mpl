<?

abstract class BaseRequest {
	protected $operationType;
	protected $err;

	protected $om;
	protected $omPeer;
	protected $omFieldNames;

	abstract function createOm();

	protected function init() {
		$this->om = $this->createOm();
		$this->omPeer = $this->om->getPeer();
		$this->omFieldNames = $this->omPeer->getFieldNames(BasePeer::TYPE_FIELDNAME);
		$this->err = array();
	}

	protected function setOmFields($data) {
		foreach ($data as $field => $value) {
			$phpName = $this->omPeer->translateFieldName($field, BasePeer::TYPE_FIELDNAME,
					BasePeer::TYPE_PHPNAME);
			call_user_func(array($this->om, "set".$phpName), $data[$field]);
		}
	}

	protected function getOmRequestData() {
		$tableMap = $this->omPeer->getTableMap();
		$ret = array();
		foreach ($this->omFieldNames as $field) {
			if (!isset($_REQUEST[$field]))
				continue;
			$column = $tableMap->getColumn($field);
			$value = $_REQUEST[$field];
			/* For text and numeric columns that can be null we translate "" to NULL. */
			if ($_REQUEST[$field] === "" && ($column->isText() || $column->isNumeric()) &&
					!$column->isNotNull())
				$value = NULL;
			$ret[$field] = $value;
		}
		return $ret;
	}

	protected function doSave() {
		$this->setOmFields($this->getOmRequestData());
		if ($this->om->validate()) {
			$this->om->save();
			return;
		}
		$this->err = $this->om->getValidationErrors();
	}

	protected function doRemove() {
		$this->setOmFields($this->getOmRequestData());
		$this->om->delete();
	}

	public function getOm() {
		return $this->om;
	}

	public function getErrors() {
		return $this->err;
	}

	public function dispatch() {
		try {
			$this->init();

			if (isset($_REQUEST['__id'])) {
				$this->om = $this->omPeer->retrieveByPk($_REQUEST['__id']);
				return;
			}

			if (isset($_REQUEST['__update'])) {
				$this->om->setNew(false);
				$this->doSave();
				return;
			}

			if (isset($_REQUEST['__add'])) {
				$this->doSave();
				return;
			}

			if (isset($_REQUEST['__remove'])) {
				$this->doRemove();
				return;
			}
		} catch (Exception $e) {
			$this->err[] = $e->getMessage();
		}
	}
}

?>
