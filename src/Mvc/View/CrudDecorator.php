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

/**
 * @property \Mindbit\Mpl\Mvc\Controller\SimpleFormRequest $request
 */
class CrudDecorator extends HtmlDecorator
{
    const TEMPLATE_CRUDFORM         = 'mindbit.mpl.crudform.html';

    const BLOCK_ID                  = 'mindbit.mpl.crud.id';

    const VAR_ACTION                = 'mindbit.mpl.crud.action';
    const VAR_ID_NAME               = 'mindbit.mpl.crud.id.name';
    const VAR_ID_VALUE              = 'mindbit.mpl.crud.id.value';

    /**
     * @param HtmlResponse $component
     */
    public function __construct($component)
    {
        parent::__construct(
            $component,
            FormDecorator::BLOCK_HIDDEN,
            self::TEMPLATE_CRUDFORM
        );
    }

    public function getSubmitAddText()
    {
        return str_pad(_('Add'), 25, ' ', STR_PAD_BOTH);
    }

    public function getSubmitUpdateText()
    {
        return str_pad(_('Update'), 25, ' ', STR_PAD_BOTH);
    }

    public function send()
    {
        switch ($this->request->getStatus()) {
            case SimpleFormRequest::STATUS_ADD:
                $this->template->setVariables([
                    self::VAR_ACTION                => SimpleFormRequest::ACTION_ADD,
                    FormDecorator::VAR_SUBMIT_VALUE => $this->getSubmitAddText(),
                ]);
                break;
            case SimpleFormRequest::STATUS_UPDATE:
                $this->template->setVariables([
                    self::VAR_ACTION                => SimpleFormRequest::ACTION_UPDATE,
                    FormDecorator::VAR_SUBMIT_VALUE => $this->getSubmitUpdateText(),
                    self::VAR_ID_NAME               => $this->request->getPrimaryKeyFieldName(),
                    self::VAR_ID_VALUE              => $this->request->getOm()->getPrimaryKey(),
                ]);
                $this->template->getBlock(self::BLOCK_ID)->show();
                break;
            default:
                return null;
        }
        $this->template->setVariables($this->getRequestVariables());
        parent::send();
    }

    /**
     * Override if request variables don't map 1:1 to the form.
     * @return array
     */
    protected function getRequestVariables()
    {
        return $this->request->getFormData();
    }
}
