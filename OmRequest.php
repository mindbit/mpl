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

require_once 'BaseRequest.php';
require_once 'PropelUtil.php';

abstract class OmRequest extends BaseRequest {
	const OPERATION_FETCH  = 1;
	const OPERATION_ADD    = 2;
	const OPERATION_UPDATE = 3;
	const OPERATION_REMOVE = 4;

	protected $operationType;
	protected $data;

	protected $om;
	protected $omPeer;
	protected $omFieldNames;

	abstract function createOm();
	protected abstract function decode();
	protected abstract function doFetch();

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

	protected function omToArray($om) {
		$ret = array();
		$om = (array)$om;
		foreach ($this->omFieldNames as $field) {
			$val = $om[PropelUtil::PROTECTED_MAGIC . $field];
			$ret[$field] = $val === NULL? "" : $val;
		}
		return $ret;
	}

	protected function arrayToOm() {
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

	protected function validate() {
		return true;
	}

	protected function doSave() {
		$this->setOmFields($this->arrayToOm());
		if (!$this->validate())
			return;
		if (!$this->om->validate()) {
			$this->err = array_merge($this->err, $this->om->getValidationFailures());
			return;
		}
		$this->__doSave();
	}

	protected function __doSave() {
		if (empty($this->err))
			$this->om->save();
	}

	protected function doRemove() {
		$this->setOmFields($this->arrayToOm());
		$this->om->delete();
	}

	function getOperationType() {
		return $this->operationType;
	}

	function getOm() {
		return $this->om;
	}

	function dispatch() {
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
	}
}

?>
