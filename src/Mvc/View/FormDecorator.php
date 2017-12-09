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

class FormDecorator extends HtmlDecorator
{
    const TEMPLATE_FORM             = 'mindbit.mpl.form.html';

    const BLOCK_CONTENT             = 'mindbit.mpl.form.content';
    const BLOCK_HIDDEN              = 'mindbit.mpl.form.hidden';

    const VAR_METHOD                = 'mindbit.mpl.form.method';
    const VAR_ACTION                = 'mindbit.mpl.form.action';
    const VAR_SUBMIT_VALUE          = 'mindbit.mpl.submit.value';

    /**
     * @param HtmlResponse $component
     * @param string $block
    */
    public function __construct($component, $block)
    {
        parent::__construct($component, $block, self::TEMPLATE_FORM);

        $this->template->setVariable(self::VAR_METHOD, self::METHOD_POST);

        // By default we repost to the request URI. Note that PHP_SELF (i.e. the script name)
        // is not what we want because typically we have rewrite rules in the web server
        // configuration.
        // FIXME: REQUEST_URI doesn't work in all environments (nginx?, apache+fcgi?)
        // FIXME: what about external query parameters that we may need to preserve?
        $this->template->setVariable(self::VAR_ACTION, $_SERVER['REQUEST_URI']);
    }
}
