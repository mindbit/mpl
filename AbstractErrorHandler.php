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
 * Clasa de pornire pentru handleri de exceptii
 *
 * Clasa implementeaza functionalitatea de baza pentru tratarea
 * exceptiilor
 */
abstract class AbstractErrorHandler {
	/**
	 * Masca de erori
	 */
	protected $mask;

	/**
	 * Matricea de obiecte de tip exceptie inregistrate pentru
	 * recovery
	protected $recovery = array();
	 */

	/**
	 * Translatia constantelor (numerice) reprezentand erori in
	 * sirurile corespunzatoare
	 */
	function errorCodeToStr($code) {
		switch($code) {
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
	
	function setMask($mask) {
		$oldMask = $this->mask;
		$this->mask = $mask;
		return $oldMask;
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
			$m = get_object_vars($var);
			if (isset($m["__varDump"]))
				return "RECURSION (&" . $var->__varDump . ")";
			$rec = $this->recursionId++;
			$ret = "object &" . $rec . " (" . get_class($var) . ") {";
			$indent2 = $indent . "  ";
			$var->__varDump = $rec;
			$this->cleanupObj[] =& $var;
			for (reset($m); null !== ($k = key($m)); next($m)) {
				$ret .= "\n" . $indent2 . $k . " => " .
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
	 * Parcurge o matrice si inlocuieste referintele spre
	 * contextul global de variabile cu sirul "__GLOBALS__"
	 */
	function removeGlobals(&$a) {
		//FIXME: ar trebui sa parcurg si membrii obiectelor
		if(!is_array($a))
			return;
		if(isset($a["errorHandlerInstance"])) {
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
			$this->removeGlobals($a[$k]);
		}
	}
	
	/**
	 * Este metoda care trebuie inregistrata prin set_error_handler()
	 *
	 * Metoda asta NU trebuie reimplementata in clasele
	 * derivate, pentru ca este doar un wrapper pentru ::HandleException()
	 * plus un test dupa masca de erori.
	 */
	public final function handleError($code, $desc, $filename, $line, &$context) {
		if ($this->mask & $code)
			return;
		if (isset($GLOBALS["__EXC_reentrancy"]))
			$this->handleReentrancy();
		$GLOBALS["__EXC_reentrancy"] = true;
		$this->__handleError($code, $desc, $filename, $line, $context);
	}
	
	/**
	 * Adevaratul handler al exceptiilor
	 * @abstract
	 *
	 * Este metoda care trateaza efectiv exceptiile. Metoda este "abstracta",
	 * adica clasele derivate sunt obligate sa o reimplementeze pentru o
	 * tratare corecta a exceptiilor.
	 */
	abstract protected function __handleError($code, $desc, $filename, $line, &$context, $backtrace = null);

	public function handleException($exception) {
		$context = null;
		// $context = $exception->getCode();
		// $context = $exception->getTrace();
		$backtrace = $this->normalizeBacktrace($exception->getTrace());
		$this->__handleError(
				E_UNHANDLED_EXCEPTION,		// code
				$exception->getMessage(),	// desc
				$exception->getFile(),		// filename
				$exception->getLine(),		// line
				$context,					// context
				$backtrace);				// backtrace
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
	 * Inregistreaza o functie de recovery pentru un tip de exceptie
	 *
	 * Daca se inregistreaza o astfel de functie, atunci ea va fi
	 * apelata la aparitia unei exceptii (invocare de ::raise()) in
	 * loc sa fie apelat handlerul implicit de erori.
	 *
	 * Daca se inregistreaza o functie de recovery pentru o clasa ea
	 * va fi implicit invocata si pentru clasele derivate (daca nu se
	 * inregistreaza explicit alte functii de recovery pentru ele).
	function registerRecoveryFunction($exception, $recovery) {
		$this->recovery[strtolower($exception)] = $recovery;
	}
	 */

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

/*
class lEXC_Exception {
	/**
	 * @static
	 * @param lEXC_ExceptionHandler $obj
	 * /

	/**
	 * @static
	 * /
	function raise($desc, $context = null) {
		$GLOBALS["lEXC_Handler"]->raise($desc, $context);
	}

	/**
	 * @static
	 * /
	function registerRecoveryFunction($exception, $recovery) {
		$GLOBALS["lEXC_Handler"]->registerRecoveryFunction($exception, $recovery);
	}

	function handler($code, $desc, $filename = null,
			$line = null, $context = null) {
		$GLOBALS["lEXC_Handler"]->handler($code, $desc, $filename, $line, $context);
	}

	function assert($cond, $context = null) {
		if (is_string($cond) ? eval($cond) : $cond)
			return;
		$bt = debug_backtrace();
		lEXC_Exception::handler(
				E_USER_WARNING,
				"Assertion failed" . (is_string($cond) ? ": " . $cond : ""),
				$bt[0]["file"],
				$bt[0]["line"],
				$context
				);
	}
}

class lEXC_Error {
    var $code;

    function lEXC_Error($code) {
        $this->code = $code;
    }

    function getCode() {
        return $this->code;
    }
}

function lEXC_isError(&$obj) {
    return is_object($obj) && is_a($obj, 'lexc_error');
}

function lEXC_assertHandler($file, $line, $message) {
	lEXC_Exception::handler(
			E_USER_WARNING,
			"Assertion failed" . (empty($message) ? "" : ": " . $message),
			$file,
			$line
			);
}

define('lEXC_Text',		1);
define('lEXC_HTML',		2);
*/

/**
 * Constanta folosita pentru a defini comportamentul unei metode
 * in cazul aparitiei unei erori.
 *
 * Comportamentul se stabileste printr-un argument cu valoare
 * implicita, pozitionat de preferinta la sfarsitul listei de argumente.
 *
 * Valorile de adevar sunt folosite in asa fel incat sa se poata folosi
 * chiar numele $raise pentru argument si sa aiba sens o constructie de
 * genul:
 *
 * if($raise)
 *     $GLOBALS['lEXC_Handler']->raise(...);
 *
 * Cateva observatii cu privire la design pattern-un raise/silent: atunci
 * cand o metoda raise/silent doreste sa propage caracterul raise/silent
 * la alte metode care folosesc accest pattern, are sens un cod de genul:
 *
 * if(($err = apel_metoda(..., $raise)) < 0)
 *     return $err;
 *
 * Este de notat ca nu se pierde nici un caz. Daca $raise este true si
 * apare o eroare in metoda apelata, atunci se va arunca acolo o exceptie.
 * Ramura cu return se va executa doar daca apare o eroare in metoda
 * apelata si $raise este false. In acest caz codul de eroare va fi propagat
 * mai sus. Pentru aceasta este esential sa nu apara suprapuneri de coduri
 * de eroare, astfel incat sa se poata detecta exact eroarea aparuta in
 * cazul apelurilor extra-modul.
 *
 * Codurile de eroare ale fiecarui modul trebuie definite adunand un numar
 * intre 0 si 0xffff la o constanta de erori specifica modulului (un numar
 * negativ, multiplu de 0x10000). In acest fel se evita suprapunerea
 * codurilor de eroare.
define('EXC_RAISE',		true);
define('EXC_SILENT',	false);
 */

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
