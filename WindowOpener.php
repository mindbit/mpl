<?

class BaseWindowOpener {

	function getWidth() {
		return 600;
	}
	
	function getHeight() {
		return 400;
	}

	function getResizable() {
		return true;
	}

	function hasLocation() {
		return true;
	}

	function getScrollbars() {
		return true;
	}

	function jsOpenFn() {
		return "ow";
	}

	function __javaScriptLoad($fn, $link, $obj, $arg) {
		?>
		function <?= $fn?>(<?= $arg?>) {
			var leftw = (window.screen.availWidth - <?= $this->getWidth()?>) / 2;
			var topw = (window.screen.availHeight - <?= $this->getHeight()?>) / 2;
			var win = window.open(
					<?= $link?>,
					<?= $obj?>,
					"left=" + leftw + "," +
					"top=" + topw + "," +
					"resizable=<?= $this->getResizable() ? "yes" : "no"?>," +
					"location=<?= $this->hasLocation() ? "1" : "0"?>," +
					"width=<?= $this->getWidth()?>," +
					"height=<?= $this->getHeight()?>," +
					"scrollbars=<?= $this->getScrollbars() ? "yes" : "no"?>");
		}
		<?
	}

	function __javaScriptNew($fn, $link, $obj, $arg) {
		?>
		function <?= $fn?>(<?= $arg?>) {
			var leftw = (window.screen.availWidth - <?= $this->getWidth()?>) / 2;
			var topw = (window.screen.availHeight - <?= $this->getHeight()?>) / 2;
			var dat = new Date();
			var uniq = dat.getHours() * 3600000 + dat.getMinutes() * 6000 + dat.getSeconds() * 1000 + dat.getMilliseconds();

			var win = window.open(
					<?= $link?>,
					<?= $obj?> + uniq,
					"left=" + leftw + "," +
					"top=" + topw + "," +
					"resizable=<?= $this->getResizable() ? "yes" : "no"?>," +
					"location=<?= $this->hasLocation() ? "yes" : "no"?>," +
					"width=<?= $this->getWidth()?>," +
					"height=<?= $this->getHeight()?>," +
					"scrollbars=<?= $this->getScrollbars() ? "yes" : "no"?>");
		}
		<?
	}

	function javaScriptOpen($link, $objName) {
		$this->__javaScriptLoad(
				$this->jsOpenFn(),
				'"' . $link . '"',
				'"' . $objName . '"',
				"");
	}
}

/**
 * Class for generating JavaScript code that opens a window.
 *
 * The main purpose is to have a common way to open new windows for a
 * specific interface (form). By using the mechanism provided by this
 * class, it's guaranteed that:
 * - the opened window for a specific interface (form) will have the same
 *   properties, regardless of where it's opened from
 * - the same object (with the same id) will not be opened in different
 *   windows (the window id is unique for a specific OM)
 */
abstract class WindowOpener extends BaseWindowOpener {
	var $callerClass;

	/**
	 * param $obj
	 *     Reference to the calling object. This parameter is used to avoid
	 *     JavaScript naming conflicts when several tabs in a multi-tabbed
	 *     form want to open the same OM form.
	 *
	 *     This parameter is passed directly by the getWindowOpener() method
	 *     of the form that the opener is attached to. Usually, the
	 *     getWindowOpener() method should be called using $this as parameter,
	 *     regardless of where the call is made.
	 */
	function __construct($obj) {
		$this->callerClass = (null === $obj ? "null" : get_class($obj));
	}

	/**
	 * Return an identified that is unique across all windows of the
	 * same type (instances of the same form).
	 */
	function getUidBase() {
		return get_class($this);
	}

	abstract function getUrl();

	/**
	 * Pentru clasele de ipwal 1, ar trebui suprascrisa aceasta
	 * metoda in loc de getUrl(), pentru a asigura transmiterea
	 * parametrului index cu numele corect (in ipwal 1 nu are
	 * numele fix __id, ci difera in functie de modul).
	 */
	function getLink() {
		return $this->getUrl() . "?__id=";
	}

	function getLinkNew() {
		return $this->getUrl() . "?__new=";
	}

	function __fnLoad() {
		return "__" . $this->getUidBase() . "__" . $this->callerClass . "__load";
	}

	function javaScriptLoad($extraParams = null, $scriptTags = true) {
		$append = "";
		if (null !== $extraParams) {
			$list = explode(",", $extraParams);
			foreach ($list as $token) {
				$token = trim($token);
				$append .= ' + "&' . $token . '=" + ' . $token;
			}
		}
		if ($scriptTags)
			echo '<script type="text/javascript">';
		$this->__javaScriptLoad(
				$this->__fnLoad(),
				'"' . $this->getLink() . '" + id' . $append,
				'"' . $this->getUidBase() . '" + id',
				"id" . (null === $extraParams ? "" : "," . $extraParams));
		if ($scriptTags)
			echo '</script>';
	}

	function __fnNew() {
		return "__" . $this->getUidBase() . "__" . $this->callerClass . "__new";
	}

	function javaScriptNew($scriptTags = true) {
		if ($scriptTags)
			echo '<script type="text/javascript">';
		$this->__javaScriptNew(
				$this->__fnNew(),
				'"' . $this->getLinkNew() . '" + append',
				'"' . $this->getUidBase() . '_uniq_"',
				"append");
		if ($scriptTags)
			echo '</script>';
	}

	function jsLinkLoad($id, $extraParams = null) {
		return "javascript:" . $this->jsInvokeLoad($id, $extraParams);
	}

	function jsInvokeLoad($id, $extraParams = null) {
		return $this->__fnLoad() . "(" . ((int)$id) .
			(null === $extraParams ? "" : "," . $extraParams) . ")";
	}

	function jsLinkNew($append = "") {
		return "javascript:" . $this->__fnNew() . "('" . $append . "')";
	}
}
?>
