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

namespace Mindbit\Mpl\Util;

use Mindbit\Mpl\Util\IPAddr;

class IPv4Addr extends IPAddr
{
    public static function validArray($f)
    {
        if (sizeof($f) > 4) {
            return false;
        }
        foreach ($f as $n) {
            if (!strlen($n) || !ctype_digit($n) || '0' == $n[0] && strlen($n) > 1) {
                return false;
            }
        }
        switch (sizeof($f)) {
            case 1:
                return (float)$f[0] < 4294967296.0;
            case 2:
                return $f[0] < 0x100 && $f[1] < 0x1000000;
            case 3:
                return $f[0] < 0x100 && $f[1] < 0x100 && $f[2] < 0x10000;
            case 4:
                return $f[0] < 0x100 && $f[1] < 0x100 && $f[2] < 0x100 &&
                    $f[3] < 0x100;
        }
        return false;
    }

    public static function validString($s)
    {
        $f = explode(".", $s);
        return self::validArray($f);
    }

    public static function validString4($s)
    {
        $f = explode(".", $s);
        return sizeof($f) == 4 && self::validArray($f);
    }

    public function set($a)
    {
        switch (gettype($a)) {
            case 'integer':
                $this->a = $a;
                return true;
            case 'string':
                if (substr($a, 0, 2) == "0x" && null !== ($this->a = $this->__hex2int($a))) {
                    return true;
                }
                if (!$this->validString($a)) {
                    $this->a = null;
                    return false;
                }
                $this->a = ip2long($a);
                return true;
            case 'array':
                if (!$this->validArray($a)) {
                    $this->a = null;
                    return false;
                }
                $this->a = ip2long(implode(".", $a));
                return true;
            case 'double':
                if ($a > 0xffffffff) {
                    $this->a = null;
                    return false;
                }
                $this->a = $this->__float2int($a);
                return true;
        }
        $this->a = null;
    }

    public function getString()
    {
        return null === $this->a ? null : long2ip($this->a);
    }

    public function getArray()
    {
        return null === $this->a ? null : explode(".", long2ip($this->a));
    }

    public function getInt()
    {
        return $this->a;
    }

    public function getLong()
    {
        return null === $this->a ? null : $this->__int2float($this->a);
    }

    public function getHex($prepend0x = true)
    {
        return null === $this->a ? null :
            $this->__int2hex($this->a, $prepend0x);
    }

    public static function __int2float($x)
    {
        return $x >= 0 ? (float)$x : (float)($x & 0x7fffffff) + 0x80000000;
        // hint: 0x80000000 este promovat implicit la float
    }

    public static function __float2int($x)
    {
        return
            $x < 0x80000000 ? (int)$x : (int)($x - 0x80000000) | -2147483648;
        // hint: -2147483648 se reprezinta pe biti ca 0x80000000 (sau 1 << 31)
    }

    public static function hexMap()
    {
        return array(
                "0"    => 0x0,
                "1" => 0x1,
                "2" => 0x2,
                "3" => 0x3,
                "4" => 0x4,
                "5" => 0x5,
                "6" => 0x6,
                "7" => 0x7,
                "8" => 0x8,
                "9" => 0x9,
                "a" => 0xa,
                "b" => 0xb,
                "c" => 0xc,
                "d" => 0xd,
                "e" => 0xe,
                "f" => 0xf,
                "A" => 0xa,
                "B" => 0xb,
                "C" => 0xc,
                "D" => 0xd,
                "E" => 0xe,
                "F" => 0xf
                    );
    }

    public static function hex()
    {
        return "0123456789abcdef";
    }

    public static function __int2hex($x, $prepend0x = true)
    {
        $hex = self::hex();
        $ret = "";
        for ($i = 0; $i < 8; $i++) {
            $ret = $hex[$x & 0xf] . $ret;
            $x >>= 4;
        }
        return ($prepend0x ? "0x" : "") . $ret;
    }

    public static function __hex2int($x)
    {
        $revHex = self::hexMap();
        if (substr($x, 0, 2) == "0x") {
            $x = substr($x, 2, strlen($x) - 2);
        }
        if (!strlen($x)) {
            return null;
        }
        for ($ret = 0; strlen($x); $x = substr($x, 1, strlen($x) - 1)) {
            if (!isset($revHex[$x[0]])) {
                return null;
            }
            $ret = ($ret << 4) | $revHex[$x[0]];
        }
        return $ret;
    }

    public static function revBits($n)
    {
        $ret = 0;
        for ($i = 0; $i < 32; $i++) {
            $ret = ($ret << 1) | ($n & 1);
            $n >>= 1;
        }
        return $ret;
    }

    public static function revBytes($n)
    {
        return
            (($n & 0xff000000) >> 24) |
            (($n & 0x00ff0000) >> 8) |
            (($n & 0x0000ff00) << 8) |
            (($n & 0x000000ff) << 24);
    }
}
