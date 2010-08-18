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
 * Client-side authentication support for SmartClient-MPL integration.
 */
isc.ClassFactory.defineClass("MplAuthenticator");
isc.MplAuthenticator.addClassProperties({
	OP_LOGIN:		1,
	OP_LOGOUT:		2,
	OP_CHECK:		3,

	defaultAuthURL:	"authenticator.php"
	});

isc.MplAuthenticator.addClassMethods({
	validateSession : function (validateSessionCallback) {
		// first check if we already are authenticated
		isc.RPCManager.sendRequest({
			containsCredentials: true,
			actionURL: isc.MplAuthenticator.defaultAuthURL,
			useSimpleHttp: true,
			evalResult: true,
			showPrompt: false,
			params: {
				operationType: isc.MplAuthenticator.OP_CHECK
			},
			callback: function(rpcResponse) {
				switch (rpcResponse.data.status) {
				case isc.RPCResponse.STATUS_SUCCESS:
					// we already are authenticated; save session data and fire
					// the callback
					isc.MplAuthenticator.session = rpcResponse.data.session;
					validateSessionCallback();
					break;
				case isc.RPCResponse.STATUS_LOGIN_REQUIRED:
					// show a login dialog that calls the server
					isc.showLoginDialog(function (credentials, dialogCallback) {
						// this must be a closure so that validateSessionCallback
						// may be visible
						if (credentials == null)
							return;

						isc.RPCManager.sendRequest({
							containsCredentials: true,
							actionURL: isc.MplAuthenticator.defaultAuthURL,
							useSimpleHttp: true,
							evalResult: true,
							showPrompt: false,
							params: {
								operationType: isc.MplAuthenticator.OP_LOGIN,
								username: credentials.username,
								password: credentials.password
							},
							callback: function(rpcResponse) {
								switch (rpcResponse.data.status) {
								case isc.RPCResponse.STATUS_LOGIN_SUCCESS:
									dialogCallback(true);
									isc.MplAuthenticator.session = rpcResponse.data.session;
									validateSessionCallback();
									break;
								case isc.RPCResponse.STATUS_LOGIN_INCORRECT:
									dialogCallback(false);
									break;
								}
							}
						});
					});
					break;
				}
			}
		});
	},

	logout : function (logoutCallback) {
		isc.RPCManager.sendRequest({
			containsCredentials: true,
			actionURL: isc.MplAuthenticator.defaultAuthURL,
			useSimpleHttp: true,
			evalResult: true,
			showPrompt: false,
			params: {
				operationType: isc.MplAuthenticator.OP_LOGOUT
			},
			callback: function(rpcResponse) {
				// FIXME check rpcResponse.data.status
				logoutCallback();
			}
		});
	}
});
