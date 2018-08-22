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
use Mindbit\Mpl\Template\Template;

/**
 * @property \Mindbit\Mpl\Search\BaseSearchRequest $request
 */
class SearchDecorator extends HtmlDecorator
{
    const TEMPLATE_SEARCHFORM   = 'mindbit.mpl.searchform.html';
    const TEMPLATE_SEARCHPAGER  = 'mindbit.mpl.searchpager.html';

    const JS_SEARCHPAGER        = 'js/mpl/searchpager.js';

    const BLOCK_FORM            = 'application.search.form';
    const BLOCK_NORESULTS       = 'application.search.noresults';
    const BLOCK_ROW             = 'application.search.row';
    const BLOCK_PAGER           = 'application.search.pager';
    const BLOCK_MRPP            = 'mindbit.mpl.search.mrpp';

    const BLOCK_PN_BACK_OFF     = 'mindbit.mpl.search.pn.back.off';
    const BLOCK_PN_BACK_ON      = 'mindbit.mpl.search.pn.back.on';
    const BLOCK_PN_BEFORE       = 'mindbit.mpl.search.pn.before';
    const BLOCK_PN_CURRENT      = 'mindbit.mpl.search.pn.current';
    const BLOCK_PN_AFTER        = 'mindbit.mpl.search.pn.after';
    const BLOCK_PN_FORWARD_OFF  = 'mindbit.mpl.search.pn.forward.off';
    const BLOCK_PN_FORWARD_ON   = 'mindbit.mpl.search.pn.forward.on';

    const VAR_ACTION            = 'mindbit.mpl.search.action';
    const VAR_PAGE_NAME         = 'mindbit.mpl.search.page.name';
    const VAR_PAGE_VALUE        = 'mindbit.mpl.search.page.value';
    const VAR_MRPP_NAME         = 'mindbit.mpl.search.mrpp.name';
    const VAR_MRPP_VALUE        = 'mindbit.mpl.search.mrpp.value';

    const VAR_PN_PAGE           = 'mindbit.mpl.search.pn.page';
    const VAR_PN_LAST           = 'mindbit.mpl.search.pn.last';

    const VAR_FIRSTINDEX        = 'mindbit.mpl.search.firstindex';
    const VAR_LASTINDEX         = 'mindbit.mpl.search.lastindex';
    const VAR_NBRESULTS         = 'mindbit.mpl.search.nbresults';

    protected $js;

    /**
     * @param HtmlResponse $component
     */
    public function __construct($component, $template = self::TEMPLATE_SEARCHPAGER, $js = self::JS_SEARCHPAGER)
    {
        parent::__construct(
            $component,
            FormDecorator::BLOCK_HIDDEN,
            self::TEMPLATE_SEARCHFORM
        );

        if ($template) {
            $this->template->replaceBlock(self::BLOCK_PAGER, Template::load($template));
        }

        $this->js = $js;
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
                $this->template->getBlock(FormDecorator::BLOCK_SUBMIT)->hide();
                break;
            case BaseSearchRequest::STATUS_RESULTS:
                $this->showPageNavigator();
                $this->showMrppOptions();
                $this->request->showResults($this);
                if ($this->js) {
                    $this->addJsRef($this->js);
                }
                $this->template->setVariables([
                    self::VAR_FIRSTINDEX    => $this->request->getFirstIndex(),
                    self::VAR_LASTINDEX     => $this->request->getLastIndex(),
                    self::VAR_NBRESULTS     => $this->request->getNbResults(),
                ]);
                $this->template->getBlock(FormDecorator::BLOCK_SUBMIT)->hide();
                break;
        }

        parent::send();
    }

    protected function showPageNavigator()
    {
        $currentPage = $this->request->getPage();

        if ($currentPage == 1) {
            $this->template->getBlock(self::BLOCK_PN_BACK_OFF)->show();
        } else {
            $this->template->setVariable(self::VAR_PN_PAGE, $currentPage - 1);
            $this->template->getBlock(self::BLOCK_PN_BACK_ON)->show();
        }

        $lastPage = $this->request->getLastPage();
        if ($currentPage == $lastPage) {
            $this->template->getBlock(self::BLOCK_PN_FORWARD_OFF)->show();
        } else {
            $this->template->setVariable(self::VAR_PN_PAGE, $currentPage + 1);
            $this->template->setVariable(self::VAR_PN_LAST, $lastPage);
            $this->template->getBlock(self::BLOCK_PN_FORWARD_ON)->show();
        }

        foreach($this->request->getLinks() as $page) {
            $this->template->setVariable(self::VAR_PN_PAGE, $page);
            if ($page < $currentPage) {
                $this->template->getBlock(self::BLOCK_PN_BEFORE)->show();
            } elseif ($page > $currentPage) {
                $this->template->getBlock(self::BLOCK_PN_AFTER)->show();
            } else {
                $this->template->getBlock(self::BLOCK_PN_CURRENT)->show();
            }
        }
    }

    protected function getMrppOptions($mrpp)
    {
        $ret = array(
            '10'    => array('text' => '10'),
            '20'    => array('text' => '20'),
            '50'    => array('text' => '50'),
            '100'   => array('text' => '100')
        );
        $ret[$mrpp]['selected'] = true;
        return $ret;
    }

    protected function showMrppOptions()
    {
        $mrpp = $this->request->getMaxPerPage();
        $this->addSelect(self::BLOCK_MRPP, $this->getMrppOptions($mrpp));
    }

    public function showResult($variables, $offset)
    {
        $block = $this->template->getBlock(self::BLOCK_ROW);
        $block->setVariables($variables);
        $block->show();
    }
}
