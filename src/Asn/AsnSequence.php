<?php
/*    Mindbit PHP Library
 *    Copyright (C) 2009 Mindbit SRL
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 2.1 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase;

class AsnSequence extends AsnBase implements IteratorAggregate, ArrayAccess
{
    public function parse($string)
    {
        //echo get_class($this) . " parsing: " ; var_dump($string);
        $this->data = array();
        $endLength = strlen($string);
        $p = 0;
        while ($p < $endLength) {
            // decode object type
            $start = $p;
            $type = ord($string[$p++]);
            if ($type == 0) {
                continue;
            }

            // decode object length
            $length = ord($string[$p++]);
            if ($length & Asn::M_LONG_LEN) {
                $length &= Asn::M_LONG_LEN - 1;
                $tempLength = 0;
                if ($p + $length > $endLength) {
                    break;
                }
                while ($length--) {
                    $tempLength = ord($string[$p++]) + ($tempLength * 256);
                }
                $length = $tempLength;
            }

            // decode object contents
            if ($p + $length > $endLength) {
                break;
            }
            switch ($type & Asn::CLASS_MASK) {
                case Asn::C_UNIVERSAL:
                    $obj = Asn::newInstance($type);
                    break;
                case Asn::C_CONTEXT:
                    // FIXME we should check that bit 5 of $type is set
                    $obj = new AsnSequenceIndex($type & Asn::TAG_MASK);
                    break;
                default:
                    $obj = new AsnUnknown($type);
            }
            $obj->parse(substr($string, $p, $length));
            $this->data[] = $obj;
            $p = $p + $length;
            $length = 0;
        }

        if ($length) {
            // we forcedly exited the loop
            $obj = new AsnUnparseable();
            $obj->parse(substr($string, $start, $endLength - $start));
            $this->data[] = $obj;
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
