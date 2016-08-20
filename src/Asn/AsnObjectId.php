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

namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase;

class AsnObjectId extends AsnBase
{
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function branch($data)
    {
        return new AsnObjectId($this->data . "." . $data);
    }

    public function parse($string)
    {
        $ret = floor(ord($string[0])/40).".";
        $ret .= (ord($string[0]) % 40);
        $build = array();
        $cs = 0;

        for ($i = 1; $i < strlen($string); $i++) {
            $v = ord($string[$i]);
            if ($v > 127) {
                $build[] = ord($string[$i]) - Asn::M_BIT;
                continue;
            }
            if (!$build) {
                $ret .= ".".$v;
                $build = array();
                continue;
            }
            // do the build here for multibyte values
            $build[] = ord($string[$i]) - Asn::M_BIT;
            // you know, it seems there should be a better way to do this...
            $build = array_reverse($build);
            $num = 0;
            $mult = 1;
            for ($x = 0; $x < count($build); $x++) {
                $value = $x + 1 >= count($build) ?
                ((($build[$x] & (Asn::M_BIT - 1)) >> $x)) * $mult :
                ((($build[$x] & (Asn::M_BIT - 1)) >> $x) ^ ($build[$x+1] << (7 - $x) & 255)) * $mult;
                $num += $value;
                $mult *= 256;
            }
            $ret .= "." . $num;
            $build = array();
        }
        $this->data = $ret;
    }
}
