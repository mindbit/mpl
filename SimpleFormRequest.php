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

abstract class SimpleFormRequest extends OmRequest {
	const OPERATION_NEW = 5;

	const STATE_ADD = 1;
	const STATE_UPDATE = 2;

	protected function decode() {
		$this->data = &$_REQUEST;

		if (isset($_REQUEST['__id'])) {
			$this->operationType = self::OPERATION_FETCH;
			return;
		}

		if (isset($_REQUEST['__update'])) {
			$this->operationType = self::OPERATION_UPDATE;
			return;
		}

		if (isset($_REQUEST['__add'])) {
			$this->operationType = self::OPERATION_ADD;
			return;
		}

		if (isset($_REQUEST['__remove'])) {
			$this->operationType = self::OPERATION_REMOVE;
			return;
		}

		if (isset($_REQUEST['__new'])) {
			$this->operationType = self::OPERATION_NEW;
			return;
		}
	}

	protected function doFetch() {
		$this->om = $this->omPeer->retrieveByPk($_REQUEST['__id']);
	}

	protected function doNew() {
	}

	function dispatch() {
		try {
			parent::dispatch();
			if ($this->operationType == self::OPERATION_NEW) {
				$this->doNew();
			}
		} catch (Exception $e) {
			$this->err[] = $e->getMessage();
		}

		switch($this->operationType) {
		case OPERATION_NEW:
		case OPERATION_REMOVE:
			$this->setState(self::STATE_ADD);
			break;
		case OPERATION_ADD:
			$this->setState(empty($this->err) ? self::STATE_UPDATE: self::STATE_ADD);
			break;
		case OPERATION_FETCH:
		case OPERATION_UPDATE:
			$this->setState(self::STATE_UPDATE);
			break;
		}
	}
}

?>
