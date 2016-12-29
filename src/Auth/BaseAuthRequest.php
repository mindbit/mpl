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

/**
 * Generic implementation of user authentication using PHP sessions.
 */
abstract class BaseAuthRequest extends BaseRequest
{
    protected $operationType;

    const OP_LOGIN              = 1;
    const OP_LOGOUT             = 2;
    const OP_CHECK              = 3;

    const SESSION_USER_KEY      = "user";

    protected $validOperationTypes = array(
            self::OP_LOGIN,
            self::OP_LOGOUT,
            self::OP_CHECK
            );

    /**
     * No user found in session, and we've just authenticated.
     */
    const S_AUTH_SUCCESS        = 1;

    /**
     * User was found in session (previously authenticated).
     */
    const S_AUTH_CACHED         = 2;

    /**
     * We tryed to authenticate the user and we failed.
     */
    const S_AUTH_FAILED         = 3;

    /**
     * Neither user nor credentials were found in request.
     */
    const S_AUTH_REQUIRED        = 4;

    public function getSessionUserKey()
    {
        $selfReflect = new \ReflectionClass($this);
        return $selfReflect->getConstant("SESSION_USER_KEY");
    }

    public function decode()
    {
        if (!isset($_REQUEST["operationType"])) {
            $this->operationType = self::OP_CHECK;
            return;
        }
        if (!in_array($_REQUEST["operationType"], $this->validOperationTypes)) {
            throw new Exception("Invalid operation type");
        }
        $this->operationType = $_REQUEST["operationType"];
    }

    public function dispatch()
    {
        //Session::setLocale();
        session_start();

        $this->decode();
        switch ($this->operationType) {
            case self::OP_LOGIN:
                $this->doLogin();
                break;
            case self::OP_LOGOUT:
                $this->doLogout();
                break;
            case self::OP_CHECK:
                $this->doCheck();
                break;
        }
    }

    public function doCheck()
    {
        $key = $this->getSessionUserKey();
        if (isset($_SESSION[$key])) {
            Session::setUser($_SESSION[$key]);
            $this->setState(self::S_AUTH_CACHED);
            return;
        }
        $this->setState(self::S_AUTH_REQUIRED);
    }

    public function doLogin()
    {
        if (($user = $this->validateUser()) === null) {
            $this->setState(self::S_AUTH_REQUIRED);
            return;
        }

        if ($user === false) {
            $this->setState(self::S_AUTH_FAILED);
            return;
        }

        Session::setUser($user);
        $_SESSION[$this->getSessionUserKey()] = $user;
        $this->setState(self::S_AUTH_SUCCESS);
    }

    public function doLogout()
    {
        unset($_SESSION[$this->getSessionUserKey()]);
        $this->setState(self::S_AUTH_REQUIRED);
    }

    /**
     * Check for credentials in request and try to authenticate user.
     *
     * This should return a valid user class instance or boolean false
     * if authentication failed.
     *
     * Additionally, a return value of null indicates that credentials
     * were not found in request.
     */
    public function validateUser()
    {
        if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) {
            return null;
        }
        $u = $this->authenticateUser($_REQUEST["username"], $_REQUEST["password"]);
        return is_object($u) ? $u : false;
    }

    /**
     * Typically, this should call the authenticate() method of your
     * user class.
     */
    abstract public function authenticateUser($username, $password);
}
