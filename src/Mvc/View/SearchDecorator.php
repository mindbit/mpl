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

use Mindbit\Mpl\Search\BaseSearchRequest;

class SearchDecorator extends HtmlDecorator
{
    const TEMPLATE_SEARCHFORM   = 'mindbit.mpl.searchform.html';

    const BLOCK_FORM            = 'application.search.form';
    const BLOCK_NORESULTS       = 'application.search.noresults';
    const BLOCK_ROW             = 'application.search.row';

    const VAR_ACTION            = 'mindbit.mpl.search.action';
    const VAR_PAGE_NAME         = 'mindbit.mpl.search.page.name';
    const VAR_PAGE_VALUE        = 'mindbit.mpl.search.page.value';
    const VAR_MRPP_NAME         = 'mindbit.mpl.search.mrpp.name';
    const VAR_MRPP_VALUE        = 'mindbit.mpl.search.mrpp.value';

    /**
     * @param HtmlResponse $component
     */
    public function __construct($component)
    {
        parent::__construct(
            $component,
            FormDecorator::BLOCK_HIDDEN,
            self::TEMPLATE_SEARCHFORM
        );
    }

    public function send()
    {
        $this->template->setVariables([
            self::VAR_ACTION                => BaseSearchRequest::ACTION_RESULTS,
            FormDecorator::VAR_SUBMIT_VALUE => 'Search',
            self::VAR_PAGE_NAME             => BaseSearchRequest::PAGE_KEY,
            self::VAR_PAGE_VALUE            => $this->request->getPage(),
            self::VAR_MRPP_NAME             => BaseSearchRequest::MRPP_KEY,
            self::VAR_MRPP_VALUE            => $this->request->getMaxPerPage(),
        ]);

        switch ($this->request->getStatus()) {
            case BaseSearchRequest::STATUS_FORM:
                $this->template->getBlock(static::BLOCK_FORM)->show();
                break;
            case BaseSearchRequest::STATUS_NORESULTS:
                $this->template->getBlock(static::BLOCK_NORESULTS)->show();
                break;
            case BaseSearchRequest::STATUS_RESULTS:
                $this->request->showResults($this);
                break;
        }

        parent::send();
    }

    public function showResult($variables, $offset)
    {
        $block = $this->template->getBlock(self::BLOCK_ROW);
        $block->setVariables($variables);
        $block->show();
    }
}
