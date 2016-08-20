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

abstract class SearchRequest extends BaseSearchRequest
{
    abstract public function createCriteria();
    abstract public function getPeerClass();

    public function getPeerSelectMethod()
    {
        return "doSelect";
    }

    public function initPager()
    {
        $this->pager = new PropelPager();
        $this->pager->setPage(1 + (int)floor($this->offset / $this->limit));
        $this->pager->setRowsPerPage($this->limit);
        $this->pager->setPeerClass($this->getPeerClass());
        $this->pager->setPeerSelectMethod($this->getPeerSelectMethod());
        $this->pager->setCriteria($this->createCriteria());
    }
}
