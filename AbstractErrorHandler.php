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

/**
 * Base class for custom error handlers.
 */
abstract class AbstractErrorHandler {
	protected static $isHandlingError = false;
	protected static $isHandlingException = false;

	/**
	 * Translate php error constants to (string) names.
	 */
	function errorCodeToStr($code) {
		switch ($code) {
		case E_ERROR:					return "ERROR";
		case E_WARNING:					return "WARNING";
		case E_PARSE:					return "PARSE";
		case E_NOTICE:					return "NOTICE";
		case E_CORE_ERROR:				return "CORE_ERROR";
		case E_CORE_WARNING:			return "CORE_WARNING";
		case E_COMPILE_ERROR:			return "COMPILE_ERROR";
		case E_COMPILE_WARNING:			return "COMPILE_WARNING";
		case E_USER_ERROR:				return "USER_ERROR";
		case E_USER_WARNING:			return "USER_WARNING";
		case E_USER_NOTICE:				return "USER_NOTICE";
		case E_UNHANDLED_EXCEPTION:		return "E_UNHANDLED_EXCEPTION";
		}
	}
	
	function varDump(&$var) {
		$this->cleanupArray = array();
		$this->cleanupObj = array();
		$this->recursionId = 1;
		$ret = $this->__varDump($var);
		for (
				reset($this->cleanupArray);
				null !== ($k = key($this->cleanupArray));
				next($this->cleanupArray))
			unset($this->cleanupArray[$k]["__varDump"]);
		for (
				reset($this->cleanupObj);
				null !== ($k = key($this->cleanupObj));
				next($this->cleanupObj))
			unset($this->cleanupObj[$k]->__varDump);
		return $ret;
	}

	function __varDump(&$var, $indent = "") {
		switch (gettype($var)) {
		case 'array':
			if (isset($var["__varDump"]))
				return "RECURSION (&" . $var["__varDump"] . ")";
			$rec = $this->recursionId++;
			$ret = "array &" . $rec . " (" . sizeof($var) . ") {";
			$var["__varDump"] = $rec;
			$this->cleanupArray[] =& $var;
			$indent2 = $indent . "  ";
			for (reset($var); null !== ($k = key($var)); next($var)) {
				if ("__varDump" === $k)
					continue;
				$ret .= "\n" . $indent2 . $k . " => " .
					$this->__varDump($var[$k], $indent2);
			}
			return $ret . "\n" . $indent . "}";
		case 'object':
			$m = (array)$var;
			if (isset($m["__varDump"]))
				return "RECURSION (&" . $var->__varDump . ")";
			$rec = $this->recursionId++;
			$ret = "object &" . $rec . " (" . get_class($var) . ") {";
			$indent2 = $indent . "  ";
			$var->__varDump = $rec;
			$this->cleanupObj[] =& $var;
			for (reset($m); null !== ($k = key($m)); next($m)) {
				do {
					$visibility = "";
					$name = $k;
					if (strlen($k) < 3 || $k[0] != "\000")
						break;
					if ($k[1] == '*') {
						$visibility = "[protected] ";
						$name = substr($k, 3);
						break;
					}
					$visibility = "[private] ";
					$name = substr(strstr(substr($k, 1), "\000"), 1);
				} while (false);
				$ret .= "\n" . $indent2 . $visibility . $name . " => " .
					$this->__varDump($m[$k], $indent2);
			}
			return $ret . "\n" . $indent . "}";
		case 'string':
			return "string (" . strlen($var) . ") \"" . $var . "\"";
		case 'boolean':
			return "boolean (" . ($var ? "true" : "false") . ")";
		default:
			return gettype($var) . " (" . $var . ")";
		}
	}
	
	/**
	 * In-depth scan of an array that replaces references to the
	 * global variable context with the "__GLOBALS__" string.
	 */
	function __removeGlobals(&$a) {
		//FIXME: ar trebui sa parcurg si membrii obiectelor
		if(!is_array($a))
			return;
		if(isset($a["__GLOBALS_MARK__"])) {
			$a = "__GLOBALS__";
			return;
		}
		for(reset($a); list($k,)=each($a);) {
			/*
			if (is_object($a[$k])) {
				$str = "<object> (" . get_class($a[$k]) . ")";
				$a[$k] =& $str;
				continue;
			}
			*/
			$this->__removeGlobals($a[$k]);
		}
	}

	function removeGlobals(&$a) {
		$GLOBALS["__GLOBALS_MARK__"] = true;
		$this->__removeGlobals($a);
		unset($GLOBALS["__GLOBALS_MARK__"]);
	}
	
	/**
	 * This is the method that should be registered with
	 * set_error_handler().
	 *
	 * This is only a wrapper for __handleError() and a test against the
	 * error mask, so descendant classes shouldn't override this.
	 */
	final function handleError($code, $desc, $filename, $line, &$context) {
		// For all the errors except php internal errors, if a
		// custom error handler is installed it will be
		// triggered regardless of the error_reporting() value. See
		// http://ro.php.net/manual/en/function.error-reporting.php#8866
		// for details.
		if (!($code & error_reporting()))
			return;

		if (self::$isHandlingError)
			$this->handleReentrancy();
		self::$isHandlingError = true;
		// Make sure we do our cleanup if __handleError throws an exception
		// (this is always the case for ThrowErrorHandler). Otherwise, we
		// may get false-positives about re-entrancy, even if the exception
		// is caught properly in the user code.
		$exception = null;
		try {
			$this->__handleError(array(
						"code"			=> $code,
						"description"	=> $desc,
						"filename"		=> $filename,
						"line"			=> $line,
						"context"		=> $context
						));
		} catch (Exception $__e) {
			$exception = $__e;
		}
		self::$isHandlingError = false;
		if ($exception !== null)
			throw $exception;
	}
	
	/**
	 * Actual error handler function.
	 *
	 * This is the method that actually handles errors. The method is
	 * abstract, so descendant classes must implement it to properly
	 * handle errors.
	 */
	abstract protected function __handleError($data);

	function handleException($exception) {
		// We detect reentrancy independent of the handleError() method
		// to behave nicely in the following scenario:
		// * __handleError throws an exception (either explicitly or
		//   implicitly)
		// * the exception is not caught anywhere, so we call
		//   __handleError again to cleanup
		if (self::$isHandlingException)
			$this->handleReentrancy();
		self::$isHandlingException = true;
		$this->__handleException($exception);
		self::$isHandlingException = false;
	}

	protected function __handleException($exception) {
		// Protect against an uncaught exception that was implicitly
		// thrown from the error handling code.
		if (self::$isHandlingError)
			$this->handleReentrancy();
		$this->__handleError($this->exceptionToErrorData($exception));
	}

	function exceptionToErrorData($e) {
		return array(
				"code"			=> E_UNHANDLED_EXCEPTION,
				"description"	=> $e->getMessage(),
				"filename"		=> $e->getFile(),
				"line"			=> $e->getLine(),
				"context"		=> null,
				"backtrace"		=> $this->normalizeBacktrace($e->getTrace())
				);
	}
	
	/**
	 * Genereaza o eroare
	 *
	 * @param mixed $desc
	 *     Descrierea erorii.
	 *
	 *     Daca este de tip object, atunci trebuie sa fie o instanta
	 *     de lEXC_Exception (sau o clasa derivata) si se incearca
	 *     invocarea unei functii de tratare a erorii (daca a fost
	 *     inregistrata cu registerRecoveryFunction()). Daca nu,
	 *     eroarea este fatala si se opreste executia.
	 *
	 *     Daca este de tip string, atunci reprezinta descrierea
	 *     erorii si executia se opreste imediat.
	 *
	 * @param array $context
	 *     Contextul in care a aparut eroarea.
	 *
	 *     Daca se opreste executia, atunci in mesajul de eroare apare
	 *     si continutul (dump) acestui array.
	 */
	function raise($desc, $context = null) {
		$code = E_USER_ERROR;
		$filename = __FILE__;
		$line = __LINE__;
		/*
		if (is_object($desc)) {
			for($class = get_class($desc); $class !== false;
					$class = get_parent_class($class)) {
				if (!isset($this->recovery[$class]))
					continue;
				call_user_func($this->recovery[$class], $desc);
				exit;
			}
			// nu am functie de recovery, fac raise normal
			$context = $desc;
			$desc = "Uncaught exception of type " . get_class($desc);
		}
		*/
		if (!is_string($desc))
			$desc = gettype($desc);
		$this->handleError($code, $desc, $filename, $line, $context);
	}

	function handleReentrancy() {
		error_reporting(E_NONE);
		$stack = debug_backtrace();
		echo "FATAL: exception reentrancy detected. Stack trace follows.\n\n";
		var_dump($stack);
		exit;
	}

	/**
	 * Apeleaza debug_backtrace() si normalizeaza rezultatul.
	 *
	 * In stiva de apel intoarsa de debug_backtrace() sunt decalate
	 * informatiile legate de fisier si linie fata de cele legate
	 * de numele functiei sau metodei. Metoda genereaza o noua stiva,
	 * cu datele aliniate.
	 */
	function normalizeBacktrace($bt = null) {
		if ($bt === null)
			$bt = debug_backtrace();
		$stack = array();
		$n = sizeof($bt);
		for($i = 0; $i <= $n; $i++) {
			$level = array();
			if($i < $n) {
				if(isset($bt[$i]['class']))
					$level['class'] =& $bt[$i]['class'];
				$level['function'] =& $bt[$i]['function'];
			}
			if($i > 0) {
				if(isset($bt[$i - 1]['file']))
					$level['file'] =& $bt[$i - 1]['file'];
				if(isset($bt[$i - 1]['line']))
					$level['line'] =& $bt[$i - 1]['line'];
			}
			$stack[] = $level;
		}
		$ret = $this->filterNormalizedBacktrace($stack);
		return $ret;
	}

	function filterNormalizedBacktrace($stack) {
		$ret = array();
		$firstLevel = 0;
		for($i = 0; $i < sizeof($stack); $i++)
			if($this->backtraceFilter($stack[$i]))
				$firstLevel = max($firstLevel, $i);
		for($i = $firstLevel + 1; $i < sizeof($stack); $i++)
			$ret[] = $stack[$i];
		return $ret;
	}

	function backtraceFilter($frame) {
		return false; // FIXME
		if (!isset($frame['function']))
			return false;
		if (!isset($frame['class']) && in_array($frame['function'], array(
						'lexc_raise',
						'lexc_asserthandler',
						'assert'
						)))
			return true;
		if (!isset($frame['class']))
			return false;
		if ($frame['class'] == 'AbstractErrorhandler' &&
				in_array($frame['function'], array(
						'handle'
						)))
			return true;
		if ($frame['class'] == get_class($this))
			return true;
		return false;
	}
	
	/**
	 * Apeleaza $this->normailzedBacktrace() si produce
	 * un output asemanator cu stiva de apeluri afisata de gdb
	 */
	function renderBacktrace($bt = null) {
		if($bt === null)
			$bt = $this->normalizeBacktrace();
		$ret = '';
		$i = 0;
		foreach($bt as $level) {
			$ret .= "#" . ($i++) . " ";
			if(isset($level['class']))
				$ret .= $level['class'] . '::';
			if(isset($level['function']))
				$ret .= $level['function'];
			$ret .= '()';
			if(isset($level['file'])) {
				$ret .= ' at ' . $level['file'];
				if(isset($level['line']))
					$ret .= ':' . $level['line'];
			}
			$ret .= "\n";
		}
		return $ret;
	}

	/**
	 * Metoda apelata automat la activarea unui nou handler de exceptii.
	 *
	 * Practic activarea inseamna setarea referintei in
	 * $GLOBALS["lEXC_Handler"]. Metoda onActivate() se apeleaza pentru
	 * obiectul nou setat.
	 */
	function onActivate() {
	}

	/**
	 * Metoda apelata automat la dezactivarea unui nou handler de exceptii.
	 *
	 * Practic dezactivarea inseamna setarea referintei in
	 * $GLOBALS["lEXC_Handler"]. Metoda onDeactivate() se apeleaza pentru
	 * obiectul referit anterior setarii.
	 */
	function onDeactivate() {
	}
}

/**
 * Constanta defineste toate erorile care nu pot fi tratate cu o functie
 * definita de utilizator. Vezi pagina de manual de la set_error_handler()
 * pentru mai multe detalii.
 */
define("E_PHPINTERNAL",	E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING |
		E_COMPILE_ERROR | E_COMPILE_WARNING);
define("E_NONE", 		0);
define("E_UNHANDLED_EXCEPTION", 0x10000);

?>
