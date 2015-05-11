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

require_once 'SmartClientRPCResponse.php';
require_once 'RequestDispatcher.php';

abstract class SmartClientAuthenticator extends RequestDispatcher {
	/**
	 * Build an associative array of data that will be passed to the
	 * SmartClient application.
	 *
	 * Typically this contains user data such as the real user's name,
	 * privileges, group membership, etc.
	 */
	function getSessionData() {
	}

	function write() {
		$r = new SmartClientRPCResponse();

		switch ($this->request->getState()) {
		case BaseAuthRequest::S_AUTH_REQUIRED:
			$r->status = SmartClientRPCResponse::STATUS_LOGIN_REQUIRED;
			break;
		case BaseAuthRequest::S_AUTH_FAILED:
			$r->status = SmartClientRPCResponse::STATUS_LOGIN_INCORRECT;
			break;
		case BaseAuthRequest::S_AUTH_SUCCESS:
			$r->status = SmartClientRPCResponse::STATUS_LOGIN_SUCCESS;
			$r->session = $this->getSessionData();
			break;
		case BaseAuthRequest::S_AUTH_CACHED:
			$r->status = SmartClientRPCResponse::STATUS_SUCCESS;
			$r->session = $this->getSessionData();
			break;
		default:
			$r->status = SmartClientRPCResponse::STATUS_FAILURE;
		}

		echo $r->jsonEncode();
	}
}
