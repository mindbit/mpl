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

require_once "../Mvc/View/BaseForm.php";

abstract class SearchForm extends BaseForm {
	protected $pager;
	protected $data;
	protected $offset, $limit;

	function __construct() {
		parent::__construct();
		$this->data = $this->request->getData();
		$this->limit = $this->request->getLimit();
		$this->offset = $this->request->getOffset();
	}

	function form() {
		echo HTML::hidden("__search_do", 1);
		echo HTML::hidden("__search_limit", $this->limit);
		switch ($this->request->getState()) {
		case SearchRequest::STATE_FORM:
			echo HTML::hidden("__search_offset", 0);
			$this->displayForm();
			break;
		case SearchRequest::STATE_RESULTS:
			echo HTML::hidden("__search_offset", $this->offset);
			$this->pager = $this->request->getPager();
			$this->displayResults();
			break;
		}
	}

	function getRppOptions() {
		return array(
				10 => "10",
				50 => "50",
				100 => "100",
				500 => "500"
				);
	}

	abstract function displayForm();

	function displayResults() {
		if ($this->pager->getTotalRecordCount()) {
			$this->displayNavBarTop();
			$this->displayResultsHeader();
			$results = $this->pager->getResult();
			foreach ($results as $result) {
				$this->offset++;
				$this->displayResult($result);
			}
			$this->displayResultsFooter();
			$this->displayNavBarBottom();
		} else {
			$this->displayNoResults();
		}
		foreach ($this->data as $key => $value)
			echo HTML::hidden($key, $value);
	}

	function displayNoResults() {
		?>
		No results match your search criteria.<br>
		<input type="button" value="New Search" onClick="searchNew()">
		<?php
	}

	function displayNavBarTop() {
		?>
		<table width="100%">
		<tr>
			<td width="33%">Results <b><?= $this->offset + 1?></b> - <b><?= min($this->offset + $this->limit, $this->pager->getTotalRecordCount())?></b> out of <b><?= $this->pager->getTotalRecordCount()?></b></td>
			<td align="center"><?php $this->displayPageList(); ?></td>
			<td width="33%" align="right">Results/page <?= HTML::select("__search_rpp", $this->limit, $this->getRppOptions(), false, null, array("onChange" => "searchChangeRpp()"))?></td>
		</tr>
		</table>
		<hr width="100%">
		<?php
	}

	function displayNavBarBottom() {
		?>
		<hr width="100%">
		<table width="100%">
		<tr>
			<td width="33%"><input type="button" value="New Search" onClick="searchNew()"></td>
			<td align="center"><?php $this->displayPageList(); ?></td>
			<td width="33%" align="right"><input type="button" value="Next Page" <?= $this->pager->getPage() < $this->pager->getTotalPages() ? "" : "disabled"?> onClick="searchPage(<?= $this->pager->getPage() + 1?>)"></td>
		</tr>
		</table>
		<?php
	}

	function displayPageList() {
		$cp = $this->pager->getPage();
		$tp = $this->pager->getTotalPages();
		echo "Page: " . HTML::link("javascript:searchPage(1)", "«", null, $cp > 1) . "&nbsp;";
		echo HTML::link("javascript:searchPage(" . ($cp - 1) . ")", "<", null, $cp > 1) . "&nbsp;";
		$prev = $this->pager->getPrevLinks();
		foreach ($prev as $page)
			echo HTML::link("javascript:searchPage(" . $page . ")", $page) . "&nbsp;";
		echo $cp . "&nbsp;";
		$next = $this->pager->getNextLinks();
		foreach ($next as $page)
			echo HTML::link("javascript:searchPage(" . $page . ")", $page) . "&nbsp;";
		echo HTML::link("javascript:searchPage(" . ($cp + 1) . ")", ">", null, $cp < $tp) . "&nbsp;";
		echo HTML::link("javascript:searchPage(" . $tp . ")", "»", null, $cp < $tp) . " out of <b>" . $tp . "</b>";
	}
	
	

	function displayResultsHeader() {
		echo '<table width="100%">';
	}

	function displayResultsFooter() {
		echo "</table>";
	}

	abstract function displayResult($result);

	function getFormAttributes() {
		return array(
				"action" => $_SERVER['PHP_SELF'],
				"method" => "post"
				);
	}

	function javaScript() {
		?>
		<script type="text/javascript">
		function searchChangeRpp() {
			document.forms[0].__search_limit.value = document.forms[0].__search_rpp.value;
			document.forms[0].__search_offset.value = "0";
			document.forms[0].submit();
		}
		function searchNew() {
			document.forms[0].__search_do.value = "0";
			document.forms[0].__search_offset.value = "0";
			document.forms[0].submit();
		}
		function searchPage(page) {
			document.forms[0].__search_offset.value = <?= $this->limit ?> * (page - 1);
			document.forms[0].submit();
		}
		</script>
		<?php
	}
}
