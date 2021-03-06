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

namespace Mindbit\Mpl;

use Mindbit\Mpl\Error\ErrorHandler;
use Mindbit\Mpl\Error\GenericErrorHandler;
use Psr\Log\LoggerInterface;

class MPL
{
    private static $logger;

    public static function get($constant, $default = null)
    {
        return defined($constant) ? constant($constant) : $default;
    }

    public static function setAssertOptions()
    {
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_BAIL, 0);
        assert_options(ASSERT_QUIET_EVAL, 0);
        assert_options(ASSERT_CALLBACK, array("ErrorHandler", "handleAssert"));
    }

    public static function init()
    {
        ErrorHandler::setHandler(new GenericErrorHandler());
        ErrorHandler::setMask(self::get("MPL_ERROR_MASK", E_NONE));

        // install custom error handlers
        set_error_handler(array('Mindbit\Mpl\Error\ErrorHandler', 'handleError'), E_ALL);
        set_exception_handler(array('Mindbit\Mpl\Error\ErrorHandler', 'handleException'));
        // self::setAssertOptions();
    }

    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public static function log($level, $message, array $context = array())
    {
        if (self::$logger != null) {
            self::$logger->log($level, $message, $context);
        }
    }

    public static function getRemoteAddr($behindProxy = false)
    {
        if ($behindProxy && isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $adr = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
            $adr = array_pop($adr);
            if ($adr != "unknown") {
                return $adr;
            }
        }
        return isset($_SERVER["REMOTE_ADDR"]) ?
            $_SERVER["REMOTE_ADDR"] : null;
    }

    public static function addIncludePath($path)
    {
        set_include_path(sprintf(
            '%s%s%s',
            get_include_path(),
            PATH_SEPARATOR,
            $path
        ));
    }
}
