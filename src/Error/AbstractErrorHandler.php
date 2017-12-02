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

namespace Mindbit\Mpl\Error;

/**
 * Base class for custom error handlers.
 */
abstract class AbstractErrorHandler
{
    protected static $isHandlingError = false;
    protected static $isHandlingException = false;

    /**
     * List of arrays that have been "seen" by varDump() recursion.
     */
    protected $vdSeenArrays;

    /**
     * List of objects that have been "seen" by varDump() recursion.
     */
    protected $vdSeenObjects;

    /**
     * varDump() recursion level.
     */
    protected $vdRecursion;

    /**
     * Translate php error constants to (string) names.
     */
    public function errorCodeToStr($code)
    {
        switch ($code) {
            case E_ERROR:
                return "ERROR";
            case E_WARNING:
                return "WARNING";
            case E_PARSE:
                return "PARSE";
            case E_NOTICE:
                return "NOTICE";
            case E_CORE_ERROR:
                return "CORE_ERROR";
            case E_CORE_WARNING:
                return "CORE_WARNING";
            case E_COMPILE_ERROR:
                return "COMPILE_ERROR";
            case E_COMPILE_WARNING:
                return "COMPILE_WARNING";
            case E_USER_ERROR:
                return "USER_ERROR";
            case E_USER_WARNING:
                return "USER_WARNING";
            case E_USER_NOTICE:
                return "USER_NOTICE";
            case E_UNHANDLED_EXCEPTION:
                return "E_UNHANDLED_EXCEPTION";
        }
    }

    public function varDump(&$var)
    {
        $this->vdSeenArrays = array();
        $this->vdSeenObjects = array();
        $this->vdRecursion = 1;
        $ret = $this->__varDump($var);
        for (reset($this->vdSeenArrays);
             null !== ($k = key($this->vdSeenArrays));
             next($this->vdSeenArrays)) {
            unset($this->vdSeenArrays[$k]["__varDump"]);
        }
        foreach ($this->vdSeenObjects as $obj) {
            unset($obj["instance"]);
        }
        $this->vdSeenObjects = array();
        return $ret;
    }

    public function __varDump(&$var, $indent = "")
    {
        switch (gettype($var)) {
            case 'array':
                if (isset($var["__varDump"])) {
                    return "RECURSION (&" . $var["__varDump"] . ")";
                }
                $rec = $this->vdRecursion++;
                $ret = "array &" . $rec . " (" . sizeof($var) . ") {";
                $var["__varDump"] = $rec;
                $this->vdSeenArrays[] =& $var;
                $indent2 = $indent . "  ";
                for (reset($var); null !== ($k = key($var)); next($var)) {
                    if ("__varDump" === $k) {
                        continue;
                    }
                    $ret .= "\n" . $indent2 . $k . " => " .
                        $this->__varDump($var[$k], $indent2);
                }
                return $ret . "\n" . $indent . "}";
            case 'object':
                $m = (array)$var;
                foreach ($this->vdSeenObjects as $obj) {
                    if ($obj["instance"] === $var) {
                        return "RECURSION (&" . $obj["recursion"] . ")";
                    }
                }
                $rec = $this->vdRecursion++;
                $ret = "object &" . $rec . " (" . get_class($var) . ") {";
                $indent2 = $indent . "  ";
                $this->vdSeenObjects[] = array("instance" => $var, "recursion" => $rec);
                for (reset($m); null !== ($k = key($m)); next($m)) {
                    do {
                        $visibility = "";
                        $name = $k;
                        if (strlen($k) < 3 || $k[0] != "\000") {
                            break;
                        }
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
    public function __removeGlobals(&$a)
    {
        //FIXME: ar trebui sa parcurg si membrii obiectelor
        if (!is_array($a)) {
            return;
        }
        if (isset($a["__GLOBALS_MARK__"])) {
            $a = "__GLOBALS__";
            return;
        }
        for (reset($a); list($k,)=each($a);) {
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

    public function removeGlobals(&$a)
    {
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
    final public function handleError($code, $desc, $filename, $line, &$context)
    {
        // For all the errors except php internal errors, if a
        // custom error handler is installed it will be
        // triggered regardless of the error_reporting() value. See
        // http://ro.php.net/manual/en/function.error-reporting.php#8866
        // for details.
        if (!($code & error_reporting())) {
            return;
        }

        if (self::$isHandlingError) {
            $this->handleReentrancy();
        }
        self::$isHandlingError = true;
        // Make sure we do our cleanup if __handleError throws an exception
        // (this is always the case for ThrowErrorHandler). Otherwise, we
        // may get false-positives about re-entrancy, even if the exception
        // is caught properly in the user code.
        $exception = null;
        try {
            $this->__handleError(array(
                        "code"            => $code,
                        "description"    => $desc,
                        "filename"        => $filename,
                        "line"            => $line,
                        "context"        => $context
                        ));
        } catch (Exception $__e) {
            $exception = $__e;
        }
        self::$isHandlingError = false;
        if ($exception !== null) {
            throw $exception;
        }
    }

    /**
     * Actual error handler function.
     *
     * This is the method that actually handles errors. The method is
     * abstract, so descendant classes must implement it to properly
     * handle errors.
     */
    abstract protected function __handleError($data);

    public function handleException($exception)
    {
        // We detect reentrancy independent of the handleError() method
        // to behave nicely in the following scenario:
        // * __handleError throws an exception (either explicitly or
        //   implicitly)
        // * the exception is not caught anywhere, so we call
        //   __handleError again to cleanup
        if (self::$isHandlingException) {
            $this->handleReentrancy();
        }
        self::$isHandlingException = true;
        $this->__handleException($exception);
        self::$isHandlingException = false;
    }

    protected function __handleException($exception)
    {
        // Protect against an uncaught exception that was implicitly
        // thrown from the error handling code.
        if (self::$isHandlingError) {
            $this->handleReentrancy();
        }
        $this->__handleError($this->exceptionToErrorData($exception));
    }

    public function exceptionToErrorData($e)
    {
        return array(
                'code'          => E_UNHANDLED_EXCEPTION,
                'description'   => get_class($e) . ': ' . $e->getMessage(),
                'filename'      => $e->getFile(),
                'line'          => $e->getLine(),
                'context'       => null,
                'backtrace'     => $this->normalizeBacktrace($e->getTrace())
                );
    }

    /**
     * Generate an error
     *
     * @param mixed $desc
     *     Error description.
     *
     *     If is object type, then it's an instance of lEXC_Exception
     *     (or a inherited class) and try an to invoke an function
     *     to treat the error (if it was registered with
     *     registerRecoveryFunction()). If not,
     *     the error was fatal and the execution stops.
     *
     *     If it is string that means that is the error description
     *     and the execution is stoped imidiatly.
     *
     * @param array $context
     *     The context in which the error occured
     *
     *     If the execution stops, then in the error message appears
     *     the dump of $context
     */
    public function raise($desc, $context = null)
    {
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
            // Don't have recovery function , do normal raise
            $context = $desc;
            $desc = "Uncaught exception of type " . get_class($desc);
        }
        */
        if (!is_string($desc)) {
            $desc = gettype($desc);
        }
        $this->handleError($code, $desc, $filename, $line, $context);
    }

    public function handleReentrancy()
    {
        error_reporting(E_NONE);
        $stack = debug_backtrace();
        echo "FATAL: exception reentrancy detected. Stack trace follows.\n\n";
        var_dump($stack);
        exit;
    }

    /**
     * Calls debug_backtrace() and normalize the result.
     *
     * In the stack call returned by the debug_backtrace() it's an offset
     * between the file, line information and those related to the
     * name of the function or method . The method generates a new stack
     * with the data aligned.
     */
    public function normalizeBacktrace($bt = null)
    {
        if ($bt === null) {
            $bt = debug_backtrace();
        }
        $stack = array();
        $n = sizeof($bt);
        for ($i = 0; $i <= $n; $i++) {
            $level = array();
            if ($i < $n) {
                if (isset($bt[$i]['class'])) {
                    $level['class'] =& $bt[$i]['class'];
                }
                $level['function'] =& $bt[$i]['function'];
            }
            if ($i > 0) {
                if (isset($bt[$i - 1]['file'])) {
                    $level['file'] =& $bt[$i - 1]['file'];
                }
                if (isset($bt[$i - 1]['line'])) {
                    $level['line'] =& $bt[$i - 1]['line'];
                }
            }
            $stack[] = $level;
        }
        $ret = $this->filterNormalizedBacktrace($stack);
        return $ret;
    }

    public function filterNormalizedBacktrace($stack)
    {
        $ret = array();
        $firstLevel = 0;
        for ($i = 0; $i < sizeof($stack); $i++) {
            if ($this->backtraceFilter($stack[$i])) {
                $firstLevel = max($firstLevel, $i);
            }
        }
        for ($i = $firstLevel + 1; $i < sizeof($stack); $i++) {
            $ret[] = $stack[$i];
        }
        return $ret;
    }

    public function backtraceFilter($frame)
    {
        return false; // FIXME
        if (!isset($frame['function'])) {
            return false;
        }
        if (!isset($frame['class']) && in_array($frame['function'], array(
                        'lexc_raise',
                        'lexc_asserthandler',
                        'assert'
                        ))) {
            return true;
        }
        if (!isset($frame['class'])) {
            return false;
        }
        if ($frame['class'] == 'AbstractErrorhandler' &&
                in_array($frame['function'], array(
                        'handle'
                        ))) {
            return true;
        }
        if ($frame['class'] == get_class($this)) {
            return true;
        }
        return false;
    }

    /**
     * Calls $this->normailzedBacktrace() and returns
     * an output equal to the stack of calls displayes by the gdb
     */
    public function renderBacktrace($bt = null)
    {
        if ($bt === null) {
            $bt = $this->normalizeBacktrace();
        }
        $ret = '';
        $i = 0;
        foreach ($bt as $level) {
            $ret .= "#" . ($i++) . " ";
            if (isset($level['class'])) {
                $ret .= $level['class'] . '::';
            }
            if (isset($level['function'])) {
                $ret .= $level['function'];
            }
            $ret .= '()';
            if (isset($level['file'])) {
                $ret .= ' at ' . $level['file'];
                if (isset($level['line'])) {
                    $ret .= ':' . $level['line'];
                }
            }
            $ret .= "\n";
        }
        return $ret;
    }

    /**
     * Method called when a nea exception handler is activated.
     *
     * That means setting a reference into $GLOBALS["lEXC_Handler"].
     * Method onActivate() is called for the new set object.
     */
    public function onActivate()
    {
    }

    /**
     * The method is called when a new exception handler is deactivated.
     *
     * That means setting a referene into $GLOBALS["lEXC_Handler"].
     * Method onDeactivate() is called for the object previous set.
     */
    public function onDeactivate()
    {
    }
}

/**
 * The constant defines all types of error that can not be treated with a user defined function.
 * See manual page for set_error_handler() for more details.
 */
define("E_PHPINTERNAL", E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING |
        E_COMPILE_ERROR | E_COMPILE_WARNING);
define("E_NONE", 0);
define("E_UNHANDLED_EXCEPTION", 0x10000);
