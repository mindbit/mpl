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

require_once "HTML.php";

abstract class BaseForm {
	protected $request;

	function __construct() {
		$this->request = $this->createRequest();
		if (null !== $this->request)
			$this->request->dispatch();
	}

	function createRequest() {
		return null;
	}

	function write() {
		?>
		<html>
		<head>
		<? $this->head(); ?>
		</head>
		<body>
		<? $this->body(); ?>
		</body>
		<?
	}

	function head() {
		$this->title();
		$this->css();
		$this->javaScript();
	}

	function title() {
	?>
	<title><?= $this->getTitle()?></title>
	<?
	}

	function css() {
	}

	function javaScript() {
	}

	function body() {
		echo HTML::tag("form", $this->getFormAttributes());
		$this->form();
		echo "</form>";
	}

	abstract function getTitle();
	abstract function getFormAttributes();

	function cssTag($url) {
		echo HTML::tag("link", array(
					"rel"	=> "stylesheet",
					"href"	=> $url,
					"type"	=> "text/css"
					), true);
	}

	function jsTag($url) {
		echo HTML::tag("script", array(
					"type"	=> "text/javascript",
					"src"	=> $url
					), true);
	}
}
?>
