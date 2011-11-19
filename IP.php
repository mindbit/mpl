<?
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

abstract class IPAddr {
	protected $a;

	abstract function set($a);

	function __construct() {
		if (func_num_args())
			call_user_func_array(array($this, 'set'), func_get_args());
	}
}

class IPv4Addr extends IPAddr {

	static function validArray($f) {
		if (sizeof($f) > 4)
			return false;
		foreach ($f as $n) {
			if (!strlen($n) || !ctype_digit($n) || '0' == $n[0] && strlen($n) > 1)
				return false;
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

	static function validString($s) {
		$f = explode(".", $s);
		return self::validArray($f);
	}

	static function validString4($s) {
		$f = explode(".", $s);
		return sizeof($f) == 4 && self::validArray($f);
	}

	function set($a) {
		switch(gettype($a)) {
		case 'integer':
			$this->a = $a;
			return true;
		case 'string':
			if (substr($a, 0, 2) == "0x" && null !== ($this->a = $this->__hex2int($a)))
				return true;
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

	function getString() {
		return null === $this->a ? null : long2ip($this->a);
	}

	function getArray() {
		return null === $this->a ? null : explode(".", long2ip($this->a));
	}

	function getInt() {
		return $this->a;
	}

	function getLong() {
		return null === $this->a ? null : $this->__int2float($this->a);
	}

	function getHex($prepend0x = true) {
		return null === $this->a ? null :
			$this->__int2hex($this->a, $prepend0x);
	}

	static function __int2float($x) {
		return $x >= 0 ? (float)$x : (float)($x & 0x7fffffff) + 0x80000000;
		// hint: 0x80000000 este promovat implicit la float
	}

	static function __float2int($x) {
		return
			$x < 0x80000000 ? (int)$x : (int)($x - 0x80000000) | -2147483648;
		// hint: -2147483648 se reprezinta pe biti ca 0x80000000 (sau 1 << 31)
	}

	static function hexMap() {
		return array(
				"0"	=> 0x0,
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
	
	static function hex() {
		return "0123456789abcdef";
	}

	static function __int2hex($x, $prepend0x = true) {
		$hex = self::hex();
		$ret = "";
		for ($i = 0; $i < 8; $i++) {
			$ret = $hex[$x & 0xf] . $ret;
			$x >>= 4;
		}
		return ($prepend0x ? "0x" : "") . $ret;
	}

	static function __hex2int($x) {
		$revHex = self::hexMap();
		if (substr($x, 0, 2) == "0x")
			$x = substr($x, 2, strlen($x) - 2);
		if (!strlen($x))
			return null;
		for ($ret = 0; strlen($x); $x = substr($x, 1, strlen($x) - 1)) {
			if (!isset($revHex[$x[0]]))
				return null;
			$ret = ($ret << 4) | $revHex[$x[0]];
		}
		return $ret;
	}

	static function revBits($n) {
		$ret = 0;
		for ($i = 0; $i < 32; $i++) {
			$ret = ($ret << 1) | ($n & 1);
			$n >>= 1;
		}
		return $ret;
	}

	static function revBytes($n) {
		return 
			(($n & 0xff000000) >> 24) |
			(($n & 0x00ff0000) >> 8) |
			(($n & 0x0000ff00) << 8) |
			(($n & 0x000000ff) << 24);
	}
}

class IPv6Addr extends IPAddr {
	function set($a) {
	}
}

class Cidr4 extends IPv4Addr {
	protected $n, $mask;

	static function __validInt($n) {
		$zero = true;
		$ret = 0;
		for ($i = 0; $i < 32; $i++) {
			$t = $n & 0x1;
			$n >>= 1;
			if (!$zero && !$t)
				return null;
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
	static function zeroBits($n) {
		for ($i = 0; $i < 32; $i++) {
			if ($n & 1)
				return $i;
			$n >>= 1;
		}
		return false;
	}

	static function maskIntNeg($n) {
		for ($ret = 0; $n; $n--)
			$ret = ($ret << 1) | 1;
		return $ret;
	}

	/**
	 * Detects all known formats and sets the address and mask fields.
	 * Address vs. mask consistency is NOT checked.
	 */
	protected function __setConvOnly($a, $n) {
		$tok = explode("/", $a);
		if (null !== $n && sizeof($tok) > 1 || sizeof($tok) > 2) {
			$this->a = $this->n = null;
			return false;
		}
		if (null === $n)
			$n = 32;
		if (sizeof($tok) > 1) {
			$a = $tok[0];
			$n = $tok[1];
		}
		if (is_string($n) && ctype_digit($n))
			$n = (int)$n;

		switch (gettype($n)) {
		case 'float':
			if ($n < 0 || $n > 0xffffffff) {
				$this->a = $this->n = null;
				return false;
			}
			$n = $this->__float2int($n);
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
	 * Seteaza campurile pentru adresa si masca facand detectarea
	 * tuturor formatelor cunoscute si conversiile necesare, si
	 * verificand consistenta adresei fata de masca.
	 *
	 * @return bool
	 *     Rezultatul conversiilor si testelor efectuate: TRUE daca
	 *     totul este ok, FALSE daca au aparut erori la parsare/conversie
	 *     sau NULL daca adresa nu este consistenta fata de masca.
	 */
	function set($a, $n = null) {
		if (!$this->__setConvOnly($a, $n))
			return false;
		$this->mask = $this->maskInt($this->n);
		if (($this->a & $this->mask) != $this->a)
			return null;
		return true;
	}

	function getMaskBitCount() {
		return $this->n;
	}

	static function maskInt($n) {
		if (null === $n)
			return null;
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

	function getMaskInt() {
		return null === $this->n ? null : $this->mask;
	}

	function getMaskFloat() {
		return null === $this->n ? null :
			$this->__int2float($this->getMaskInt());
	}

	function getMaskString() {
		if (null === $this->n)
			return null;
		$a = new IPv4Addr();
		$a->set($this->mask);
		return $a->getString();
	}

	function getStringBitCount() {
		return null === $this->a ? null :
			$this->getString() . "/" . $this->getMaskBitCount();
	}

	function getMaskHex($prepend0x = true) {
		return null === $this->n ? null :
			$this->__int2hex($this->mask, $prepend0x);
	}


	function getStringString() {
		return null === $this->a ? null :
			$this->getString() . "/" . $this->getMaskString();
	}

	function matches(IPv4Addr $addr) {
		if (null === $this->a || null === $addr->a)
			return null;
		return ($addr->a & $this->mask) == $this->a;
	}

	/*
	function normalize() {
		if (null === $this->a || null === $this->mask)
			return;
		$this->a &= $this->mask;
	}
	*/
}

class Cidr6 extends IPv6Addr {
}

?>
