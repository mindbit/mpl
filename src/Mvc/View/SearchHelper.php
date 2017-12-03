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

use Mindbit\Mpl\Template\Template;
use Mindbit\Mpl\Search\BaseSearchRequest;

class SearchHelper extends FormHelper
{
    const TEMPLATE_SEARCHFORM   = 'mindbit.mpl.searchform.html';

    const VAR_PAGE_NAME         = 'mindbit.mpl.search.page.name';
    const VAR_PAGE_VALUE        = 'mindbit.mpl.search.page.value';
    const VAR_MRPP_NAME         = 'mindbit.mpl.search.mrpp.name';
    const VAR_MRPP_VALUE        = 'mindbit.mpl.search.mrpp.value';

    public function getVariables()
    {
        return [
            self::VAR_SUBMIT_VALUE => 'Search'
        ];
    }

    public function getBlock()
    {
        /**
         * @var \Mindbit\Mpl\Search\BaseSearchRequest $request
         */
        $request = $this->response->getRequest();
        $template = Template::load(self::TEMPLATE_SEARCHFORM);
        $template->setVariables([
            self::VAR_PAGE_NAME     => BaseSearchRequest::PAGE_KEY,
            self::VAR_PAGE_VALUE    => $request->getPage(),
            self::VAR_MRPP_NAME     => BaseSearchRequest::MRPP_KEY,
            self::VAR_MRPP_VALUE    => $request->getMaxPerPage(),
            self::VAR_ACTION_VALUE  => BaseSearchRequest::ACTION_RESULTS
        ]);
        return $template->getBlock(null);
    }
}
