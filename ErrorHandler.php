<?

require_once("AbstractErrorHandler.php");

class ErrorHandler {
	static function setHandler(AbstractErrorHandler $obj) {
		if (isset($GLOBALS["errorHandlerInstance"])) {
			// apelez onDeactivate() pe vechiul handler;
			// ma bazez si pe faptul ca isset() intoarce false daca
			// variabila este setata, dar contine null - in caz ca am
			// asa ceva in $GLOBALS["lEXC_Handler"], sa nu incerc
			// invocare de metoda pe referinta null.
			$GLOBALS["errorHandlerInstance"]->onDeactivate();
		}
		$GLOBALS["errorHandlerInstance"] = $obj;
		if (null !== $obj)
			$obj->onActivate();
	}

	static function getHandler() {
		if (!isset($GLOBALS["errorHandlerInstance"]))
			return null;
		return $GLOBALS["errorHandlerInstance"];
	}

	static function setMask($mask) {
		return $GLOBALS["errorHandlerInstance"]->setMask($mask);
	}

	static function varDump(&$var) {
		return $GLOBALS["errorHandlerInstance"]->varDump($var);
	}

	static function handle($code, $desc, $filename = null, $line = null, $context = null) {
		return $GLOBALS["errorHandlerInstance"]->handle($code, $desc, $filename, $line, $context);
	}

	static function raise($desc, $context = null) {
		return $GLOBALS["errorHandlerInstance"]->raise($desc, $context);
	}
}

?>
