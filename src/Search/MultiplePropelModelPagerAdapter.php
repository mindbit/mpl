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

class MultiplePropelModelPagerAdapter extends BasePagerAdapter
{
    protected $pagers = array();
    protected $pager;
    protected $offset;
    protected $limit;

    public function __construct($queries, $offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $nr = count($queries);
        for ($i = 0; $i < $nr; $i++) {
            $this->pagers[$i] = $queries[$i]->paginate(1 + (int)floor($this->offset / $this->limit), $this->limit);
            $this->pagers[$i]->peer = $queries[$i]->getModelPeerName();
            $this->pagers[$i]->q = $queries[$i];
        }
    }

    public function getTotalRecordCount()
    {
        $total = 0;
        $good = false;
        $nr = count($this->pagers);
        for ($i = 0; $i < $nr; $i++) {
            if (!empty($this->pagers[$i])) {
                //$_SESSION['totalRange'][$i]=$this->pagers[$i]->count();
                $total += $this->pagers[$i]->count();
                $good = true;
            }
        }
        if (!$good) {
            return 0;
        }
        return $total;
    }

    public function getPage()
    {
        return 1 + (int)floor($this->offset / $this->limit);
    }

    public function getTotalPages()
    {
        try {
            $tp = ceil($this->getTotalRecordCount()/$this->limit);
        } catch (Exception $e) {
            $tp = 0;
        }
        return $tp;
    }

    public function getFirstPage()
    {
        return 1;
    }

    public function getLastPage()
    {
        $last = 1;
        foreach ($this->pagers as $pg) {
            $last += $pg->getLastPage();
        }
        return $last;
    }

    public function getResult()
    {
        $nr = count($this->pagers);
        for ($i = 0; $i < $nr; $i++) {
            $pg = $this->pagers[$i];
            $tb = $pg->peer;

            if (($this->offset < $pg->count())  && (($this->offset + $this->limit) <= $pg->count())) {
                //sufficient by current;
                $pg->q->setLimit($this->limit);
                $pg->q->setOffset($this->offset);
                $result = $tb::doSelect($pg->q);
                return $result;
            }
            if (($this->offset < $pg->count())  && (($this->offset + $this->limit) >  $pg->count())) {
                //page needs need more: $this->offset+$this->limit -$pg->count()
                $pg->q->setLimit($this->limit);
                $pg->q->setOffset($this->offset);
                $result = $tb::doSelect($pg->q);

                if (!empty($this->pagers[$i+1])) {
                    $fill_peer = $this->pagers[$i+1]->peer;
                    $fill_query = $this->pagers[$i+1]->q;

                    $fill_query->setLimit(($this->offset + $this->limit) - $pg->count());
                    $fill_query->setOffset(0);

                    $fill_result = $fill_peer::doSelect($fill_query);
                    $final_result = array_merge($result, $fill_result);

                    return $final_result;
                }
                return $result;
            }
        }
        return null;
    }
}
