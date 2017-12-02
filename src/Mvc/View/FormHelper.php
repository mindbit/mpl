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

namespace Mindbit\Mpl\Mvc\View;

abstract class FormHelper
{
    const VAR_SUBMIT_VALUE  = 'mindbit.mpl.submit.value';
    const VAR_ACTION_VALUE  = 'mindbit.mpl.action.value';

    protected $response;

    /**
     * @param HtmlResponse $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    public abstract function getVariables();
    public abstract function getBlock();
}
