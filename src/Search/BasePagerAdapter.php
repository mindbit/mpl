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

abstract class BasePagerAdapter
{
    abstract public function __construct($pager);

    abstract public function getTotalRecordCount();

    abstract public function getPage();

    abstract public function getTotalPages();

    abstract public function getFirstPage();

    abstract public function getLastPage();

    abstract public function getResult();

    public function getPrevLinks($range = 5)
    {
        $total      = $this->getTotalPages();
        $start      = $this->getPage() - 1;
        $end        = $this->getPage() - $range;
        $first      = $this->getFirstPage();
        $links      = array();
        for ($i=$start; $i>$end; $i--) {
            if ($i < $first) {
                break;
            }
            $links[] = $i;
        }

        return array_reverse($links);
    }

    public function getNextLinks($range = 5)
    {
        $total      = $this->getTotalPages();
        $start      = $this->getPage() + 1;
        $end        = $this->getPage() + $range;
        $last       = $this->getLastPage();
        $links      = array();
        for ($i=$start; $i<$end; $i++) {
            if ($i > $last) {
                break;
            }
            $links[] = $i;
        }

        return $links;
    }
}
