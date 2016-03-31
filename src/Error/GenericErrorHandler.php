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

require_once 'AbstractErrorHandler.php';
require_once '../Util/AsciiTable.php';
require_once '../Deprecated/Version.php';

define("EXC_TABLE_WIDTH",    72);
define("EXC_LEFT_WIDTH",     25);
define("EXC_RIGHT_WIDTH",    EXC_TABLE_WIDTH - EXC_LEFT_WIDTH);

define("STR_ALIGN_LEFT",		STR_PAD_RIGHT);
define("STR_ALIGN_CENTER",		STR_PAD_BOTH);
define("STR_ALIGN_RIGHT",		STR_PAD_LEFT);

class GenericErrorHandler extends AbstractErrorHandler {
	const DISPLAY_FORMAT_NONE	= 0;
	const DISPLAY_FORMAT_HTML	= 1;
	const DISPLAY_FORMAT_TEXT	= 2;

	const MAIL_TO				= "mail_to";
	const MAIL_FROM				= "mail_from";

	protected $displayFormat = self::DISPLAY_FORMAT_NONE;
	protected $outputBuffering = false;
	protected $initialObLevel;
	protected $mailProps;

	function __construct() {
		$this->displayFormat =
			isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ?
			self::DISPLAY_FORMAT_HTML : self::DISPLAY_FORMAT_TEXT;
	}

	function onActivate() {
		if ($this->outputBuffering) {
			$this->initialObLevel = ob_get_level();
			ob_start();
		}
	}

	function onDeactivate() {
		if ($this->outputBuffering) {
			while (ob_get_level() > $this->initialObLevel)
				ob_end_flush();
		}
	}

	/**
	 * Get the output buffer contents on ALL LEVELS and also DISABLE
	 * outbut buffering.
	 */
	function getOutputBuffer($all = false) {
		$ret = "";
		$limit = $all ? 0 : $this->initialObLevel;
		while (ob_get_level() > $limit) {
			$ret = ob_get_contents() . $ret;
			// FIXME concatenating does not work well with handlers that make changes
			// to buffers (such as the gzhandler)
			ob_end_clean();
		}
		return $ret;
	}

	/**
	 * Disabled output buffering on all levels and discards any output
	 * that may have been captured (buffered).
	 *
	 * This method is used when a large amount of data needs to be sent
	 * to the client (usually this happens with file download handlers
	 * or when data is fetched from another server using the curl
	 * extension).
	 */
	function disableBuffering($all = true) {
		echo $this->getOutputBuffer($all);
	}

	function displayErrorHeader() {
		switch ($this->displayFormat) {
		case self::DISPLAY_FORMAT_HTML:
			?>
			<center>
			<table border="1" cellspacing="1">
			<tr>
			<th bgcolor="black" colspan="2"><font color="white">Error</th></th>
			</tr>
			<?php
			break;
		case self::DISPLAY_FORMAT_TEXT:
			echo AsciiTable::renderRowSeparator(array(EXC_TABLE_WIDTH + 3));
			echo AsciiTable::renderCells(
					array('ERROR'), array(EXC_TABLE_WIDTH + 3),
					array(1), array(STR_ALIGN_CENTER));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			break;
		}
	}

	function displayErrorBody($data) {
		switch ($this->displayFormat) {
		case self::DISPLAY_FORMAT_HTML:
			?>
			<tr>
				<td bgcolor="white"><b>Error code</b></td>
				<td bgcolor="white"><?= $data["textCode"]?></td>
			</tr>
			<tr>
				<td bgcolor="white"><b>Error description</b></td>
				<td bgcolor="white"><?= $data["description"]?></td>
			</tr>
			<tr>
				<td bgcolor="white"><b>File</b></td>
				<td bgcolor="white"><?= $data["filename"]?></td>
			</tr>
			<tr>
				<td bgcolor="white"><b>Line</b></td>
				<td bgcolor="white"><?= $data["line"]?></td>
			</tr>
			<tr>
				<td bgcolor="white"><b>Backtrace</b></td>
				<td bgcolor="white"><?= nl2br($data["renderedBacktrace"])?></td>
			</tr>
			<tr>
				<td bgcolor="white"><b>Context</b></td>
				<td bgcolor="white"><pre><?= htmlentities($data["dumpedContext"])?></pre></td>
			</tr>
			<?php
			break;
		case self::DISPLAY_FORMAT_TEXT:
			echo AsciiTable::renderCells(
					array("Error code", $data["textCode"]),
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderCells(
					array("Error description", $data["description"]),
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderCells(
					array("File", $data["filename"]),
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderCells(
					array("Line", $data["line"]),
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderCells(
					array("Backtrace", $data["renderedBacktrace"]),
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderCells(
					array("Context", $data["dumpedContext"]),
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			echo AsciiTable::renderRowSeparator(
					array(EXC_LEFT_WIDTH, EXC_RIGHT_WIDTH));
			break;
		}
	}

	function displayErrorFooter() {
		switch ($this->displayFormat) {
		case self::DISPLAY_FORMAT_HTML:
			?>
			</center>
			</table>
			<?php
			break;
		}
	}

	function formatErrorHeader() {
		return "============================== ERROR ==============================\n";
	}

	function formatErrorBody($data) {
		$ret = '';

		$ret .= "== Code:        " . $data["textCode"] . "\n";
		$ret .= "== Description: " . $data["description"] . "\n";
		$ret .= "== Filename:    " . $data["filename"] . "\n";
		$ret .= "== Line:        " . $data["line"] . "\n";

		if (($remoteAddr = $this->getRemoteAddr()) != null)
			$ret .= "== Remote IP:   " . $remoteAddr . "\n";

		$ret .= "== Backtrace:" . "\n";
		$backtrace = explode("\n", $data["renderedBacktrace"]);
		foreach ($backtrace as $frame) {
			if (!strlen(trim($frame)))
				continue;
			$ret .= "==     " . $frame . "\n";
		}

		$ret .= "== Context:";
		$_context = explode("\n", $data["dumpedContext"]);
		foreach ($_context as $c)
			$ret .= "==     " . $c . "\n";

		return $ret;
	}

	function formatErrorFooter() {
		return "===================================================================\n";
	}

	function logException($e) {
		$this->log("============================ EXCEPTION ============================");
		do {
			$msgs = explode("\n", $e->__toString());
			foreach ($msgs as $msg)
				$this->log("== " . $msg);
			if (PHP_VERSION_ID < 50300)
				break;
			//$this->log("============================ caused by ============================");
			$e = $e->getPrevious();
		} while ($e !== null);
		$this->log("===================================================================");
	}

	function mailErrorSubject($data) {
		return "[" . php_uname("n") . "] error in file " . $data["filename"] .
			", line " . $data["line"];
	}

	function logErrorHeader() {
		$this->logFormatted($this->formatErrorHeader());
	}

	function logErrorBody($data) {
		$this->logFormatted($this->formatErrorBody($data));
	}

	function logErrorFooter() {
		$this->logFormatted($this->formatErrorFooter());
	}

	function log($message) {
		MPL::log($message);
	}

	function logFormatted($message) {
		foreach (explode("\n", rtrim($message, "\n")) as $message)
			$this->log($message);
	}

	function getRemoteAddr() {
		return isset($_SERVER["REMOTE_ADDR"]) ?
			$_SERVER["REMOTE_ADDR"] : null;
	}

	function handleSingleError($data) {
		$data["textCode"] = $this->errorCodeToStr($data["code"]) . " (" . $data["code"] . ")";
		$data["renderedBacktrace"] = $this->renderBacktrace(isset($data["backtrace"]) ? $data["backtrace"] : null);
		$data["dumpedContext"] = $this->varDump($data["context"]);
		$this->removeGlobals($data["context"]);

		if ($this->outputBuffering)
			$data["outputBuffer"] = $this->getOutputBuffer();

		// log
		$this->logErrorHeader();
		$this->logErrorBody($data);
		$this->logErrorFooter();

		// send mail
		if ($this->mailProps) {
			mail($this->mailProps[self::MAIL_TO], $this->mailErrorSubject($data),
					$this->formatErrorHeader() .
					$this->formatErrorBody($data) .
					$this->formatErrorFooter(),
					"From: " . $this->mailProps[self::MAIL_FROM],
					"-f" . $this->mailProps[self::MAIL_FROM]);
		}

		// display
		$this->displayErrorHeader();
		$this->displayErrorBody($data);
		$this->displayErrorFooter();
	}

	protected function __handleError($data) {
		$this->handleSingleError($data);
		exit;
	}

	protected function __handleException($exception) {
		// Protect against an uncaught exception that was implicitly
		// thrown from the error handling code.
		if (self::$isHandlingError)
			$this->handleReentrancy();
		if (PHP_VERSION_ID < 50300) {
			$this->handleSingleError($this->exceptionToErrorData($exception));
			return;
		}
		for ($e = $exception; $e !== null; $e = $e->getPrevious())
			$this->handleSingleError($this->exceptionToErrorData($e));
	}

	/* FIXME
	function backtraceFilter(&$frame) {
		if (isset($frame["class"]) && $frame["class"] == "ErrorHandler" && $frame["function"] == "handleError")
			return true;
		return parent::backtraceFilter($frame);
	}
	*/
}
