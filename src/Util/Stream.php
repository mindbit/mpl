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

namespace Mindbit\Mpl\Util;

class Stream
{
    protected $stream;

    /* Just as a reminder:
       Big Endian    = MSB at lowest address
       Little Endian = LSB at lowest address
     */

    public function __construct($stream = null)
    {
        $this->stream = $stream;
    }

    public function readIntBE()
    {
    }

    public function readIntLE()
    {
        if (strlen($s = fread($this->stream, 4)) < 4) {
            return null;
        }

        $x = 0;

        for ($i = 3; $i >= 0; $i--) {
            $x = ($x << 8) | ord($s[$i]);
        }

        return $x;
    }

    public function readByte()
    {
        if (($c = fgetc($this->stream)) === false) {
            return null;
        }
        return ord($c);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->stream, $offset, $whence);
    }

    public function read($length = 4096)
    {
        return fread($this->stream, $length);
    }
}
