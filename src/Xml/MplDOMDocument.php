<?php
require_once "../Error/ThrowErrorHandler.php";

class MplDOMDocument extends DOMDocument {
	function load($filename, $options = 0) {
		$exception = null;
		$prevErrorHandler = ErrorHandler::setHandler(new ThrowErrorHandler());
		try {
			$ret = parent::load($filename, $options);
		} catch (Exception $__e) {
			$exception = $__e;
		}
		ErrorHandler::setHandler($prevErrorHandler);
		if ($exception !== null)
			throw $exception;
		return $ret;
	}
}
