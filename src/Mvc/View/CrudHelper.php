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

use Mindbit\Mpl\Mvc\Controller\SimpleFormRequest;
use Mindbit\Mpl\Template\Template;

class CrudHelper
{
    const TEMPLATE_CRUDFORM = 'mindbit.mpl.crudform.html';

    const BLOCK_ID          = 'mindbit.mpl.crud.id';

    const VAR_SUBMIT_VALUE  = 'mindbit.mpl.submit.value';
    const VAR_ACTION_VALUE  = 'mindbit.mpl.action.value';
    const VAR_ID_NAME       = 'mindbit.mpl.crud.id.name';
    const VAR_ID_VALUE      = 'mindbit.mpl.crud.id.value';

    protected $response;

    /**
     * @param HtmlResponse $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    public function getSubmitAddText()
    {
        return str_pad(_('Add'), 25, ' ', STR_PAD_BOTH);
    }

    public function getSubmitUpdateText()
    {
        return str_pad(_('Update'), 25, ' ', STR_PAD_BOTH);
    }

    public function getSubmitVariables()
    {
        switch ($this->response->getRequest()->getStatus()) {
            case SimpleFormRequest::STATUS_ADD:
                $value = $this->getSubmitAddText();
                break;
            case SimpleFormRequest::STATUS_UPDATE:
                $value = $this->getSubmitUpdateText();
                break;
            default:
                return null;
        }
        return [
            self::VAR_SUBMIT_VALUE => $value
        ];
    }

    public function getInputHiddenBlock()
    {
        $template = Template::load(self::TEMPLATE_CRUDFORM);
        switch ($this->response->getRequest()->getStatus()) {
            case SimpleFormRequest::STATUS_ADD:
                $template->setVariable(self::VAR_ACTION_VALUE, SimpleFormRequest::ACTION_ADD);
                break;
            case SimpleFormRequest::STATUS_UPDATE:
                $request = $this->response->getRequest();
                assert($request instanceof \Mindbit\Mpl\Mvc\Controller\OmRequest);
                $template->setVariable(self::VAR_ACTION_VALUE, SimpleFormRequest::ACTION_UPDATE);
                $template->setVariable(self::VAR_ID_NAME, $request->getPrimaryKeyFieldName());
                $template->setVariable(self::VAR_ID_VALUE, $request->getOm()->getPrimaryKey());
                $template->getBlock(self::BLOCK_ID)->show();
                break;
            default:
                return null;
        }
        return $template->getBlock(null);
    }
}
