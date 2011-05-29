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
require_once 'HTTP.php';

abstract class SearchRequest extends BaseRequest {
	const STATE_FORM = 1;
	const STATE_RESULTS = 2;

	/**
	 * Array that contains search keywords
	 */
	protected $data;
	protected $offset;
	protected $limit;
	protected $pager;

	protected function init() {
		$this->data = $this->initData();
		$this->pager = new PropelPager();
		$this->limit = 10;
		$this->offset = 0;
	}

	/**
	 * Returns an array that contains the default values for the search
	 * keywords.
	 */
	abstract function initData();

	protected function decode() {
		foreach ($this->data as $key => $ignore) {
			if (isset($_REQUEST[$key]))
				$this->data[$key] = $_REQUEST[$key];
		}

		$this->offset = HTTP::inVar("__search_offset", 0, "integer");
		$this->limit = HTTP::inVar("__search_limit", 10, "integer");
	}

	abstract function createCriteria();
	abstract function getPeerClass();
	
	function getPeerSelectMethod() {
		return "doSelect";
	}

	function dispatch() {
		$this->init();
		$this->decode();

		$this->pager->setPage(1 + (int)floor($this->offset / $this->limit));
		$this->pager->setRowsPerPage($this->limit);

		if (!HTTP::inVar("__search_do")) {
			$this->setState(self::STATE_FORM);
			return;
		}

		$this->setState(self::STATE_RESULTS);
		$this->pager->setPeerClass($this->getPeerClass());
		$this->pager->setPeerSelectMethod($this->getPeerSelectMethod());
		$this->pager->setCriteria($this->createCriteria());
	}

	function getPager() {
		return $this->pager;
	}

	function getData() {
		return $this->data;
	}
}

?>
