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

namespace Mindbit\Mpl\Search;

use Mindbit\Mpl\Mvc\Controller\BaseRequest;
use Mindbit\Mpl\Util\HTTP;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;

abstract class BaseSearchRequest extends BaseRequest
{
    const ACTION_FORM       = 'form';
    const ACTION_RESULTS    = 'results';
    const DEFAULT_ACTION    = self::ACTION_FORM;

    const STATUS_FORM       = self::ACTION_FORM;
    const STATUS_RESULTS    = self::ACTION_RESULTS;
    const STATUS_NORESULTS  = 'noresults';

    const PAGE_KEY          = 'search_page';
    const MRPP_KEY          = 'search_mrpp';

    protected $pager;

    protected function actionForm()
    {
        $this->setStatus(self::STATUS_FORM);
    }

    protected function actionResults()
    {
        $this->pager = $this->buildQuery()->paginate(
            $_REQUEST[self::PAGE_KEY],
            $_REQUEST[self::MRPP_KEY]
        );
        $this->setStatus($this->pager->getNbResults() ? self::STATUS_RESULTS : self::STATUS_NORESULTS);
    }

    protected function getRowVariables($om)
    {
        return $om->toArray(TableMap::TYPE_FIELDNAME);
    }

    public function getFormData()
    {
        return [];
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $query
     * @param string $column
     * @param string $value
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    public function addLike($query, $column, $value)
    {
        $value = trim($value);
        if (strlen($value)) {
            $query->addUsingAlias($column, '%' . $value . '%', Criteria::LIKE);
        }
        return $query;
    }

    public function getPage()
    {
        return $this->pager ? $this->pager->getPage() : 1;
    }

    public function getMaxPerPage()
    {
        return $this->pager ? $this->pager->getMaxPerPage() : 10;
    }

    public function getFirstIndex()
    {
        return $this->pager->getFirstIndex();
    }

    public function getLastIndex()
    {
        return $this->pager->getLastIndex();
    }

    public function getNbResults()
    {
        return $this->pager->getNbResults();
    }

    /**
     * @param \Mindbit\Mpl\Mvc\View\SearchDecorator $decorator
     */
    public function showResults($decorator)
    {
        $offset = 0;
        foreach ($this->pager as $om) {
            $decorator->showResult($this->getRowVariables($om), $offset++);
        }
    }

    /**
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    protected abstract function buildQuery();
}

abstract class __BaseSearchRequest
{
    /**
     * Array that contains search keywords
     */
    protected $data;
    protected $offset;
    protected $limit;
    protected $pager;

    protected function init()
    {
        $this->data = $this->initData();
        $this->limit = 10;
        $this->offset = 0;
    }

    /**
     * Returns an array that contains the default values for the search
     * keywords.
     */
    abstract public function initData();

    protected function decode()
    {
        foreach ($this->data as $key => $ignore) {
            if (isset($_REQUEST[$key])) {
                $this->data[$key] = $_REQUEST[$key];
            }
        }

        $this->offset = HTTP::inVar("__search_offset", 0, "integer");
        $this->limit = HTTP::inVar("__search_limit", 10, "integer");
    }

    public function dispatch()
    {
        $this->init();
        $this->decode();

        if (!HTTP::inVar("__search_do")) {
            $this->setState(self::STATE_FORM);
            return;
        }

        $this->setState(self::STATE_RESULTS);
        $this->initPager();
    }

    abstract public function initPager();

    public function getPager()
    {
        return $this->pager;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setQueryPager($query)
    {
        $this->pager = new PropelModelPagerAdapter(
            $query->paginate(
                1 + (int)floor($this->offset / $this->limit),
                $this->limit
            )
        );
    }

    public function setCombinedQueryPager($queries)
    {
        $this->pager = new MultiplePropelModelPagerAdapter($queries, $this->offset, $this->limit);
    }
}
