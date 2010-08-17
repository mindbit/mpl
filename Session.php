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

// require_once 'GenericUser.php';
// require_once "Locale.php";

class Session {
	protected static $user = null;

	static function setUser($user) {
		self::$user = $user;
	}

	static function getUser() {
		return self::$user;
	}

	/* TODO these need to be standardized and are not ready for use
	static function setLocale() {
		header("Content-type: text/html; charset=utf-8");
		Locale::setDirectory(realpath(dirname(__FILE__) . "/../locale"));
		Locale::set(LC_MESSAGES, "ro_RO");
	}

	static function log($message, $priority) {
		Config::$log->log(
				"[" . $_SERVER['REMOTE_ADDR'] . "|" . $_SERVER['REMOTE_PORT'] . "] " .
				"[" . (self::$user === null ? "<anonymous>" : self::$user->getUsername()) . "] " .
				$message,
				$priority
				);
	}

	static function logQuery($sql) {
		self::log("[QUERY] " . $sql, PEAR_LOG_DEBUG);
	}
	*/
}

?>
