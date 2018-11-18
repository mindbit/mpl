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

class ErrorHandler
{
    protected static $handlerInstance = null;

    public static function setHandler(AbstractErrorHandler $obj)
    {
        if (self::$handlerInstance !== null) {
            self::$handlerInstance->onDeactivate();
        }
        $ret = self::$handlerInstance;
        self::$handlerInstance = $obj;
        if (null !== $obj) {
            $obj->onActivate();
        }
        return $ret;
    }

    public static function getHandler()
    {
        return self::$handlerInstance;
    }

    public static function addMask($mask)
    {
        $ret = ~error_reporting();
        error_reporting(~($ret | $mask));
        return $ret;
    }

    public static function setMask($mask)
    {
        return ~error_reporting(~$mask);
    }

    public static function varDump(&$var)
    {
        return self::$handlerInstance->varDump($var);
    }

    public static function handleError($code, $desc, $filename = null, $line = null, $context = null)
    {
        // NOTE: When custom error handling is enabled using the
        //       set_error_handler() function, errors are no longer
        //       filtered automatically by PHP.
        return (error_reporting() & $code) ?
            self::$handlerInstance->handleError($code, $desc, $filename, $line, $context) :
            true;
    }

    public static function handleException($exception)
    {
        return self::$handlerInstance->handleException($exception);
    }

    public static function logException($exception)
    {
        self::$handlerInstance->logException($exception);
    }

    public static function handleAssert($file, $line, $message)
    {
        return self::$handlerInstance->handleAssert($file, $line, $message);
    }

    public static function raise($desc, $context = null)
    {
        return self::$handlerInstance->raise($desc, $context);
    }
}
