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
namespace Mindbit\Mpl\Auth;

use Mindbit\Mpl\Mvc\Controller\BaseRequest;
use Mindbit\Mpl\Session\Session;
use Mindbit\Mpl\Mvc\View\NullResponse;

/**
 * Generic implementation of user authentication using PHP sessions.
 */
abstract class BaseAuthRequest extends BaseRequest
{
    const ACTION_CHECK      = 'check';
    const ACTION_LOGIN      = 'login';
    const ACTION_LOGOUT     = 'logout';

    const DEFAULT_ACTION    = self::ACTION_CHECK;

    const SESSION_USER_KEY  = 'user';

    /**
     * Cached authentication check was successful. User was previously
     * authenticated.
     */
    const STATUS_CACHED     = 1;

    /**
     * Cached authentication check failed. Previous authentication data
     * was not found in session and user needs to authenticate again.
     */
    const STATUS_REQUIRED   = 2;

    /**
     * User credentials validation failed. User does not exist or the
     * supplied credentials were invalid.
     */
    const STATUS_FAILED     = 3;

    public function getSessionUserKey()
    {
        $selfReflect = new \ReflectionClass($this);
        return $selfReflect->getConstant('SESSION_USER_KEY');
    }

    public function handle()
    {
        //Session::setLocale();
        session_start();
        parent::handle();

        if ($this->status == self::STATUS_FAILED || $this->status == self::STATUS_REQUIRED) {
            exit();
        }
    }

    protected function actionCheck()
    {
        $key = $this->getSessionUserKey();
        if (isset($_SESSION[$key])) {
            Session::setUser($_SESSION[$key]);
            $this->setStatus(self::STATUS_CACHED);
            $this->response = new NullResponse($this);
            return;
        }
        $this->setStatus(self::STATUS_REQUIRED);
    }

    protected function actionLogin()
    {
        $user = $this->authenticateUser();
        if (!$user) {
            $this->setStatus(self::STATUS_FAILED);
            return;
        }

        Session::setUser($user);
        $_SESSION[$this->getSessionUserKey()] = $user;
        $this->setStatus(self::STATUS_SUCCESS);
        $this->response = new NullResponse($this);
    }

    protected function actionLogout()
    {
        unset($_SESSION[$this->getSessionUserKey()]);
        $this->setStatus(self::STATUS_REQUIRED);
    }

    /**
     * Get user credentials from $_REQUEST and authenticate user.
     * @return object
     */
    abstract protected function authenticateUser();
}
