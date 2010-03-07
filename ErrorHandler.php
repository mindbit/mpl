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

	static function handleError($code, $desc, $filename = null, $line = null, $context = null) {
		return $GLOBALS["errorHandlerInstance"]->handleError($code, $desc, $filename, $line, $context);
	}

	static function handleException($exception) {
		return $GLOBALS["errorHandlerInstance"]->handleException($exception);
	}

	static function raise($desc, $context = null) {
		return $GLOBALS["errorHandlerInstance"]->raise($desc, $context);
	}
}

?>
