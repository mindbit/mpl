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

use Mindbit\Mpl\Util\IPv4Addr;

class Cidr4 extends IPv4Addr
{
    protected $n;
    protected $mask;

    public static function __validInt($n)
    {
        $zero = true;
        $ret = 0;
        for ($i = 0; $i < 32; $i++) {
            $t = $n & 0x1;
            $n >>= 1;
            if (!$zero && !$t) {
                return null;
            }
            if ($t) {
                $zero = false;
                $ret++;
            }
        }
        return $ret;
    }

    /**
     * Counts least significant "0" bits.
     */
    public static function zeroBits($n)
    {
        for ($i = 0; $i < 32; $i++) {
            if ($n & 1) {
                return $i;
            }
            $n >>= 1;
        }
        return false;
    }

    public static function maskIntNeg($n)
    {
        for ($ret = 0; $n; $n--) {
            $ret = ($ret << 1) | 1;
        }
        return $ret;
    }

    /**
     * Detects all known formats and sets the address and mask fields.
     * Address vs. mask consistency is NOT checked.
     */
    protected function __setConvOnly($a, $n)
    {
        $tok = explode("/", $a);
        if (null !== $n && sizeof($tok) > 1 || sizeof($tok) > 2) {
            $this->a = $this->n = null;
            return false;
        }
        if (null === $n) {
            $n = 32;
        }
        if (sizeof($tok) > 1) {
            $a = $tok[0];
            $n = $tok[1];
        }
        if (is_string($n) && ctype_digit($n)) {
            $n = (int)$n;
        }

        switch (gettype($n)) {
            case 'float':
                if ($n < 0 || $n > 0xffffffff) {
                    $this->a = $this->n = null;
                    return false;
                }
                $n = $this->__float2int($n);
                //intentional fall-through
            case 'integer':
                if ($n >= 0 && $n <= 32) {
                    $this->n = $n;
                    break;
                }
                if (null === ($this->n = $this->__validInt($n))) {
                    $this->a = null;
                    return false;
                }
                break;
            case 'string':
                if (!parent::set($n)) {
                    $this->n = null;
                    return false;
                }
                if (null === ($this->n = $this->__validInt($this->a))) {
                    $this->a = null;
                    return false;
                }
                break;
        }

        if (!parent::set($a)) {
            $this->n = null;
            return false;
        }

        return true;
    }

    /**
     * Set address and mask fields making detection
     * of all known formats and all necessary conversion,
     * also the consistency of the address versus mask is checked.
     *
     * @return bool
     *      The conversion and tests result: TRUE if ok,
     *      FALSE if errors occured when parsing/converting or
     *      NULL if address is not mask consistent.
     */
    public function set($a, $n = null)
    {
        if (!$this->__setConvOnly($a, $n)) {
            return false;
        }
        $this->mask = $this->maskInt($this->n);
        if (($this->a & $this->mask) != $this->a) {
            return null;
        }
        return true;
    }

    public function getMaskBitCount()
    {
        return $this->n;
    }

    public static function maskInt($n)
    {
        if (null === $n) {
            return null;
        }
        $ret = 0;
        for ($i = 0; $i < 32; $i++) {
            $ret <<= 1;
            if ($n) {
                $ret |= 0x1;
                $n--;
            }
        }
        return $ret;
    }

    public function getMaskInt()
    {
        return null === $this->n ? null : $this->mask;
    }

    public function getMaskFloat()
    {
        return null === $this->n ? null :
            $this->__int2float($this->getMaskInt());
    }

    public function getMaskString()
    {
        if (null === $this->n) {
            return null;
        }
        $a = new IPv4Addr();
        $a->set($this->mask);
        return $a->getString();
    }

    public function getStringBitCount()
    {
        return null === $this->a ? null :
            $this->getString() . "/" . $this->getMaskBitCount();
    }

    public function getMaskHex($prepend0x = true)
    {
        return null === $this->n ? null :
            $this->__int2hex($this->mask, $prepend0x);
    }

    public function getStringString()
    {
        return null === $this->a ? null :
            $this->getString() . "/" . $this->getMaskString();
    }

    public function matches(IPv4Addr $addr)
    {
        if (null === $this->a || null === $addr->a) {
            return null;
        }
        return ($addr->a & $this->mask) == $this->a;
    }

    /*
    public function normalize() {
        if (null === $this->a || null === $this->mask)
            return;
        $this->a &= $this->mask;
    }
    */
}
