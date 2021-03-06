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

namespace Mindbit\Mpl\SmartClient;

use Mindbit\Mpl\Mvc\View\BaseForm;

/**
 * Protect a DataSource server from being accessed without authentication.
 */
abstract class AuthForm extends BaseForm
{
    public function write()
    {
        switch ($this->request->getState()) {
            case BaseAuthRequest::S_AUTH_CACHED:
                // the user is already authenticated and we pass control to
                // any subsequent RequestDispatcher
                return;
            case BaseAuthRequest::S_AUTH_REQUIRED:
                // call parent implementation to display login status code
                // markers and trigger login processing in client RPCManager
                parent::write();

                // prevent any subsequent RequestDispatcher from being called
                exit;
        }
    }

    public function form()
    {
        // TODO when authentication fails, respond with SmartClient login
        // status code markers; see RPCManager.processLoginStatusText()
    }

    public function getTitle()
    {
    }

    public function getFormAttributes()
    {
    }
}
