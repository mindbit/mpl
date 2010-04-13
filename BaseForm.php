<?
require_once "HTML.php";

abstract class BaseForm {
	public function write() {
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

	public function head() {
		$this->title();
		$this->css();
		$this->javaScript();
	}

	public function title() {
	?>
	<title><?= $this->getTitle()?></title>
	<?
	}

	public function css() {
	}

	public function javaScript() {
	}

	public function body() {
		echo HTML::tag("form", $this->getFormAttributes());
		$this->form();
		echo "</form>";
	}

	public abstract function getTitle();
	public abstract function getFormAttributes();

	public function cssTag($url) {
		echo HTML::tag("link", array(
					"rel"	=> "stylesheet",
					"href"	=> $url,
					"type"	=> "text/css"
					), true);
	}

	public function jsTag($url) {
		echo HTML::tag("script", array(
					"type"	=> "text/javascript",
					"src"	=> $url
					), true);
	}
}
?>
