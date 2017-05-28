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

namespace Mindbit\Mpl\Mvc\Controller;

abstract class BaseRequest
{
    const VALID_ACTION      = '/^\w+$/';
    const DEFAULT_ACTION    = null;

    const STATUS_SUCCESS    = 0;

    protected $action;
    protected $response;
    protected $errors;
    protected $status = self::STATUS_SUCCESS;

    public function handle()
    {
        $this->action = $this->deriveAction();
        if (!preg_match(self::VALID_ACTION, $this->action)) {
            throw new InvalidActionException($this->action);
        }

        $this->response = $this->createResponse();

        $actionMethod = 'action' . ucfirst($this->action);
        if (!method_exists($this, $actionMethod)) {
            throw new UndefinedActionException($this->action);
        }
        call_user_func(array($this, $actionMethod));

        $this->response->send();
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Examine the request data and derive the action
     *
     * @return string
     */
    protected function deriveAction()
    {
        if (isset($_REQUEST['action'])) {
            return $_REQUEST['action'];
        }

        $selfReflect = new \ReflectionClass($this);
        $action = $selfReflect->getConstant('DEFAULT_ACTION');
        if (!$action) {
            throw new InvalidActionException();
        }

        return $action;
    }

    /**
     * Instantiate the response class
     *
     * @return \Mindbit\Mpl\Mvc\View\BaseResponse
     */
    abstract protected function createResponse();

    public function getStatus()
    {
        return $this->status;
    }

    protected function setStatus($status)
    {
        $this->status = $status;
    }
}
