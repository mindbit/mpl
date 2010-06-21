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

require_once "OmRequest.php";
require_once "RequestDispatcher.php";
require_once "HTML.php";

abstract class BaseForm extends RequestDispatcher {
	// constants for coding various doctypes
	// bits 0 to 7 are for minor version, 8 to 15 for major
	const DT_STRICT			= 0x00010000;
	const DT_TRANSITIONAL	= 0x00020000;
	const DT_FRAMESET		= 0x00030000;
	const DT_XHTML			= 0x00100000;

	// valid doctypes in W3C Recommendations
	// for further info, see http://www.w3schools.com/tags/tag_DOCTYPE.asp
	const DT_HTML_4_01_STRICT		= 0x00010401; // DT_STRICT | 0x401;
	const DT_HTML_4_01_TRANSITIONAL	= 0x00020401; // DT_TRANSITIONAL | 0x401;
	const DT_HTML_4_01_FRAMESET		= 0x00030401; // DT_FRAMESET | 0x401;
	const DT_XHTML_1_0_STRICT		= 0x00110100; // DT_XHTML | DT_STRICT | 0x100;
	const DT_XHTML_1_0_TRANSITIONAL	= 0x00120100; // DT_XHTML | DT_TRANSITIONAL | 0x100;
	const DT_XHTML_1_0_FRAMESET		= 0x00130100; // DT_XHTML | DT_FRAMESET | 0x100;
	const DT_XHTML_1_1				= 0x00100101; // DT_XHTML | 0x101;

	// constants-to-uri map for valid doctypes
	protected static $doctypeMap = array(
			self::DT_HTML_4_01_STRICT		=> '"-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"',
			self::DT_HTML_4_01_TRANSITIONAL	=> '"-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"',
			self::DT_HTML_4_01_FRAMESET		=> '"-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"',
			self::DT_XHTML_1_0_STRICT		=> '"-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"',
			self::DT_XHTML_1_0_TRANSITIONAL	=> '"-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"',
			self::DT_XHTML_1_0_FRAMESET		=> '"-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd"',
			self::DT_XHTML_1_1				=> '"-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"'
			);

	const MEDIA_SCREEN		= "screen";
	const MEDIA_PRINT		= "print";

	protected $om;

	abstract function form();
	abstract function getTitle();
	abstract function getFormAttributes();

	function __construct() {
		parent::__construct();

		if ($this->request instanceof OmRequest)
			$this->om = $this->request->getOm();
	}

	function getDoctype() {
	}

	function tag($name, $attr = array(), $close = false) {
		$dt = $this->getDoctype();
		$close = $close ?
			($dt === null || !($dt & self::DT_XHTML) ? HTML::TAG_CLOSE_HTML : HTML::TAG_CLOSE_XHTML) :
			HTML::TAG_CLOSE_NONE;
		return HTML::tag($name, $attr, $close);
	}

	function write() {
		$doctype = $this->getDoctype();
		if (null !== $doctype)
			echo "<!DOCTYPE " . self::$doctypeMap[$doctype] . ">\n";
		?>
		<html>
		<head>
		<? $this->head(); ?>
		</head>
		<body>
		<? $this->body(); ?>
		</body>
		</html>
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

	function cssTag($url, $media = null) {
		$attr = array(
					"rel"	=> "stylesheet",
					"href"	=> $url,
					"type"	=> "text/css"
					);
		if ($media !== null)
			$attr["media"] = $media;
		echo $this->tag("link", $attr,
				($this->getDoctype() & self::DT_XHTML) ? HTML::TAG_CLOSE_XHTML : HTML::TAG_CLOSE_NONE);
	}

	function jsTag($url) {
		echo $this->tag("script", array(
					"type"	=> "text/javascript",
					"src"	=> $url
					), true);
	}
}
?>
