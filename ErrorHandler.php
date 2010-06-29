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

require_once("AbstractErrorHandler.php");

class ErrorHandler {
	protected static $handlerInstance = null;

	static function setHandler(AbstractErrorHandler $obj) {
		if (self::$handlerInstance !== null)
			self::$handlerInstance->onDeactivate();
		self::$handlerInstance = $obj;
		if (null !== $obj)
			$obj->onActivate();
	}

	static function getHandler() {
		return self::$handlerInstance;
	}

	static function setMask($mask) {
		return self::$handlerInstance->setMask($mask);
	}

	static function varDump(&$var) {
		return self::$handlerInstance->varDump($var);
	}

	static function handleError($code, $desc, $filename = null, $line = null, $context = null) {
		return self::$handlerInstance->handleError($code, $desc, $filename, $line, $context);
	}

	static function handleException($exception) {
		return self::$handlerInstance->handleException($exception);
	}

	static function raise($desc, $context = null) {
		return self::$handlerInstance->raise($desc, $context);
	}
}

?>
