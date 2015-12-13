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

require_once 'BaseRequest.php';
require_once 'HTTP.php';

abstract class BaseSearchRequest extends BaseRequest {
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

	function dispatch() {
		$this->init();
		$this->decode();

		if (!HTTP::inVar("__search_do")) {
			$this->setState(self::STATE_FORM);
			return;
		}

		$this->setState(self::STATE_RESULTS);
		$this->initPager();
	}

	abstract function initPager();

	function getPager() {
		return $this->pager;
	}

	function getData() {
		return $this->data;
	}

	function getLimit() {
		return $this->limit;
	}

	function getOffset() {
		return $this->offset;
	}

	function addLike($criteria, $column, $field) {
		if (!strlen($this->data[$field]))
			return $criteria;
		$criteria->add($column, '%' . trim($this->data[$field]) . '%', Criteria::LIKE);
		return $criteria;
	}

	function setQueryPager($query) {
		$this->pager = new PropelModelPagerAdapter($query->paginate(1 + (int)floor($this->offset / $this->limit),$this->limit));
	}
	
	function setCombinedQueryPager($queries) {
		$this->pager = new MultiplePropelModelPagerAdapter($queries, $this->offset, $this->limit);
	}
}

abstract class SearchRequest extends BaseSearchRequest {
	abstract function createCriteria();
	abstract function getPeerClass();

	function getPeerSelectMethod() {
		return "doSelect";
	}

	function initPager() {
		$this->pager = new PropelPager();
		$this->pager->setPage(1 + (int)floor($this->offset / $this->limit));
		$this->pager->setRowsPerPage($this->limit);
		$this->pager->setPeerClass($this->getPeerClass());
		$this->pager->setPeerSelectMethod($this->getPeerSelectMethod());
		$this->pager->setCriteria($this->createCriteria());
	}
}


abstract class BasePagerAdapter {

	abstract function __construct($pager);

	abstract function getTotalRecordCount();

	abstract function getPage();

	abstract function getTotalPages();

	abstract function getFirstPage();

	abstract function getLastPage();

	abstract function getResult();
	
	public function getPrevLinks($range = 5) {
		$total 	= $this->getTotalPages();
		$start 	= $this->getPage() - 1;
		$end 	= $this->getPage() - $range;
		$first 	= $this->getFirstPage();
		$links 	= array();
		for ($i=$start; $i>$end; $i--) {
			if ($i < $first) {
				break;
			}
			$links[] = $i;
		}

		return array_reverse($links);
	}

	public function getNextLinks($range = 5) {
		$total 	= $this->getTotalPages();
		$start 	= $this->getPage() + 1;
		$end 	= $this->getPage() + $range;
		$last 	= $this->getLastPage();
		$links 	= array();
		for ($i=$start; $i<$end; $i++) {
			if ($i > $last) {
				break;
			}
			$links[] = $i;
		}

		return $links;
	}
} 

/**
* Encapsulate a PropelModelPager and expose required PropelPager
* functionality.
*/
class PropelModelPagerAdapter extends BasePagerAdapter {
	protected $pager;

	function __construct($pager) {
		$this->pager = $pager;
	}

	function getTotalRecordCount() {
		return $this->pager->count();
	}

	function getPage() {
		return $this->pager->getPage();
	}

	function getTotalPages() {
		return $this->pager->getLastPage();
	}

	function getFirstPage() {
		return $this->pager->getFirstPage();
	}

	function getLastPage() {
		return $this->pager->getLastPage();
	}

	function getResult() {
		return $this->pager->getResults();
	}
}

class MultiplePropelModelPagerAdapter extends BasePagerAdapter {
	protected $pagers = array();
	protected $pager;
	protected $offset;
	protected $limit;
	
	function __construct($queries,$offset,$limit) {
	
		$this->offset= $offset;
		$this->limit = $limit;
		$nr=count($queries);
		for($i=0;$i<$nr;$i++) {
			$this->pagers[$i] = $queries[$i]->paginate(1 + (int)floor($this->offset / $this->limit), $this->limit);	
			$this->pagers[$i]->peer  = $queries[$i]->getModelPeerName();
			$this->pagers[$i]->q  = $queries[$i];
		}
	}

	function getTotalRecordCount() {
		$total 	= 0;
		$good	= false;
		$nr=count($this->pagers);
		for($i=0;$i<$nr;$i++) {
			if(!empty($this->pagers[$i])) {
				//$_SESSION['totalRange'][$i]=$this->pagers[$i]->count();
				$total+=$this->pagers[$i]->count();
				$good=true;
			}
		}
		if(!$good)
			return 0;
		return $total;
	}

	function getPage() {
		return 1 + (int)floor($this->offset / $this->limit);
	}

	function getTotalPages() {
		try {
			$tp = ceil($this->getTotalRecordCount()/$this->limit);
		}
		catch(Exception $e) {
			$tp=0;
		}
		return $tp;
	}

	function getFirstPage() {
		return 1;
	}

	function getLastPage() {
		$last = 1;
		foreach($this->pagers as $pg)
			$last+=$pg->getLastPage();
		return $last;
	}

	function getResult() {
	
		$nr=count($this->pagers);
		for($i=0;$i<$nr;$i++) {
			$pg = $this->pagers[$i];
			$tb = $pg->peer;
			
			if(($this->offset < $pg->count())  && (($this->offset + $this->limit) <= $pg->count())){
				//sufficient by current;
				$pg->q->setLimit($this->limit);
				$pg->q->setOffset($this->offset);
				$result = $tb::doSelect($pg->q);
				return $result;
			}
			if(($this->offset < $pg->count())  && (($this->offset + $this->limit) >  $pg->count())){
				//page needs need more: $this->offset+$this->limit -$pg->count() 
				$pg->q->setLimit($this->limit);
				$pg->q->setOffset($this->offset);
				$result = $tb::doSelect($pg->q);

				if(!empty($this->pagers[$i+1])){
					$fill_peer 	= $this->pagers[$i+1]->peer;
					$fill_query 	= $this->pagers[$i+1]->q;

					$fill_query->setLimit(($this->offset + $this->limit) - $pg->count());
					$fill_query->setOffset(0);
					
					$fill_result 	= $fill_peer::doSelect($fill_query);
					$final_result 	= array_merge($result, $fill_result);
					
					return $final_result;
				}	
				return $result;
			}
		}
		return null;
	}
}
?>
