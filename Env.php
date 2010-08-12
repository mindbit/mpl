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

require_once("ErrorHandler.php");
require_once("GenericErrorHandler.php");

class Env {
	static function get($constant, $default = null) {
		return defined($constant) ? constant($constant) : $default;
	}

	static function setAssertOptions() {
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_BAIL, 0);
		assert_options(ASSERT_QUIET_EVAL, 0);
		assert_options(ASSERT_CALLBACK, 'lEXC_assertHandler');
		// FIXME handlerul trebuie sa fie apel static catre o metoda din ErrorHandler
	}

	static function setup() {
		// Leave active only php internal errors (which are not passed
		// to user-defined error handlers). For all other errors, if a
		// custom error handler is installed (like here), it will be
		// triggered regardless of the error_reporting() value. See
		// http://ro.php.net/manual/en/function.error-reporting.php#8866
		// for details.
		error_reporting(E_PHPINTERNAL);

		ErrorHandler::setHandler(new GenericErrorHandler());
		ErrorHandler::setMask(Env::get("MPL_ERROR_MASK", E_NONE));

		// install custom error handlers
		set_error_handler(array("ErrorHandler", "handleError"));
		set_exception_handler(array("ErrorHandler", "handleException"));
		// Env::setAssertOptions();
	}
}

?>
