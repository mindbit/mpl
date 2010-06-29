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

require_once('AbstractErrorHandler.php');

abstract class LoggingErrorHandler extends AbstractErrorHandler {
	function renderExceptionElements(&$code, &$desc, &$filename,
			&$line, &$context, &$backtrace) {
	}

	function logHeader() {
		$this->log("============================ EXCEPTION ============================");
	}

	function logErrorCode($code) {
		$this->log("== Code:        " . $this->errorCodeToStr($code)." (".$code.")");
	}

	function logDescription($desc) {
		$this->log("== Description: " . $desc);
	}

	function logFilename($filename) {
		$this->log("== Filename:    " . $filename);
	}

	function logLine($line) {
		$this->log("== Line:        " . $line);
	}

	function logRemoteIp() {
		if (!isset($_SERVER["REMOTE_ADDR"]))
			return;
		$this->log("== Remote IP:   " . $_SERVER["REMOTE_ADDR"]);
	}

	function logBacktrace($backtrace) {
		$this->log("== Backtrace:");
		$backtrace = explode("\n", $this->renderBacktrace($backtrace));
		foreach ($backtrace as $frame) {
			if (!strlen(trim($frame)))
				continue;
			$this->log("==     " . $frame);
		}
	}

	function logContext(&$context) {
		$this->log("== Context:");
		$_context = explode("\n", $this->varDump($context));
		foreach ($_context as $c)
			$this->log("==     " . $c);
	}

	function logFooter() {
		$this->log("===================================================================");
	}

	function __handleError($code, $desc, $filename, $line, &$context, $backtrace = null) {
		$this->removeGlobals($context);
		$this->renderExceptionElements($code, $desc, $filename, $line, $context, $backtrace);
		$this->logHeader();
		$this->logErrorCode($code);
		$this->logDescription($desc);
		$this->logFilename($filename);
		$this->logLine($line);
		$this->logRemoteIp();
		$this->logBacktrace($backtrace);
		$this->logContext($context);
		$this->logFooter();
		exit;
	}

	abstract function log($message);

	/* FIXME
	function backtraceFilter($frame) {
		if (isset($frame["class"]) && $frame["class"] == "ErrorHandler" && $frame["function"] == "handleError")
			return true;
		return parent::backtraceFilter($frame);
	}
	*/
}

?>
