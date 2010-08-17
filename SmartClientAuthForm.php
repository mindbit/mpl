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

require_once "BaseForm.php";

/**
 * Protect a DataSource server from being accessed without authentication.
 */
abstract class SmartClientAuthForm extends BaseForm {
	function write() {
		switch ($this->request->getState()) {
		case AuthRequest::S_AUTH_CACHED:
			// the user is already authenticated and we pass control to
			// any subsequent RequestDispatcher
			return;
		case AuthRequest::S_AUTH_REQUIRED:
			// call parent implementation to display login status code
			// markers and trigger login processing in client RPCManager
			parent::write();

			// prevent any subsequent RequestDispatcher from being called
			exit;
		}
	}

	function form() {
		// TODO when authentication fails, respond with SmartClient login
		// status code markers; see RPCManager.processLoginStatusText()
	}

	function getTitle() {
	}

	function getFormAttributes() {
	}
}

?>
