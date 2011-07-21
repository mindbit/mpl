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

/*
 * This class is based on asn1.php from the Mistpark project.
 */

class Asn {
	// markers
	const M_UNIVERSAL		= 0x00;
	const M_APPLICATION		= 0x40;
	const M_CONTEXT			= 0x80;
	const M_PRIVATE			= 0xC0;

	const M_PRIMITIVE		= 0x00;
	const M_CONSTRUCTOR		= 0x20;

	const M_LONG_LEN		= 0x80;
	const M_EXTENSION_ID	= 0x1F;
	const M_BIT				= 0x80;

	// types
	const T_BOOLEAN			= 1;
	const T_INTEGER			= 2;
	const T_BIT_STR			= 3;
	const T_OCTET_STR		= 4;
	const T_NULL			= 5;
	const T_OBJECT_ID		= 6;
	const T_REAL			= 9;
	const T_ENUMERATED		= 10;
	const T_RELATIVE_OID	= 13;
	const T_PRINT_STR		= 19;
	const T_IA5_STR			= 22;
	const T_UTC_TIME		= 23;
	const T_GENERAL_TIME	= 24;
	const T_SEQUENCE		= 48;
	const T_SET				= 49;

	static function getClassMap() {
		return array(
				self::T_BOOLEAN			=> "AsnBoolean",
				self::T_INTEGER			=> "AsnInteger",
				self::T_BIT_STR			=> "AsnBitStr",
				self::T_OCTET_STR		=> "AsnOctetStr",
				self::T_NULL			=> "AsnNull",
				self::T_OBJECT_ID		=> "AsnObjectId",
				self::T_REAL			=> "AsnReal",
				self::T_ENUMERATED		=> "AsnEnumerated",
				self::T_RELATIVE_OID	=> "AsnRelativeOid",
				self::T_PRINT_STR		=> "AsnPrintStr",
				self::T_IA5_STR			=> "AsnIa5Str",
				self::T_UTC_TIME		=> "AsnUtcTime",
				self::T_GENERAL_TIME	=> "AsnGeneralTime",
				self::T_SEQUENCE		=> "AsnSequence",
				self::T_SET				=> "AsnSet"
				);
	}

	static function newInstance($type) {
		$classMap = self::getClassMap();
		$class = $classMap[$type];
		return new $class;
	}

	static function parse($string) {
		$ret = new AsnSequence();
		$ret->parse($string);
		return $ret;
	}
}

abstract class AsnBase {
	protected $data;

	function getData() {
		return $this->data;
	}

	abstract function parse($string);
}

class AsnBoolean extends AsnBase {
	function parse($string) {
		$this->data = (bool)$string;
	}
}

class AsnInteger extends AsnBase {
	function parse($string) {
		$this->data = strtr(base64_encode($string),'+/','-_');
	}
}

class AsnOctetStr extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnNull extends AsnBase {
	function parse($string) {
		$this->data = null;
	}
}

class AsnObjectId extends AsnBase {
	function __construct($data = null) {
		$this->data = $data;
	}

	function branch($data) {
		return new AsnObjectId($this->data . "." . $data);
	}

	function parse($string) {
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

class AsnReal extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnRelativeOid extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnPrintStr extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnIa5Str extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnUtcTime extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnGeneralTime extends AsnBase {
	function parse($string) {
		$this->data = $string;
	}
}

class AsnSequence extends AsnBase implements IteratorAggregate, ArrayAccess {
	function parse($string) {
		//echo get_class($this) . " parsing: " ; var_dump($string);
		$this->data = array();
		$endLength = strlen($string);
		$bigLength = $length = $type = $dtype = $p = 0;
		while ($p < $endLength) {
			$type = ord($string[$p++]);
			$dtype = ($type & 192) >> 6;
			if ($type == 0)
				continue;

			$length = ord($string[$p++]);
			if ($length & Asn::M_LONG_LEN) {
				$tempLength = 0;
				for ($x = 0; $x < ($length & (Asn::M_LONG_LEN - 1)); $x++)
					$tempLength = ord($string[$p++]) + ($tempLength * 256);
				$length = $tempLength;
			}
			$obj = Asn::newInstance($type);
			$obj->parse(substr($string, $p, $length));
			$this->data[] = $obj;
			$p = $p + $length;
		}
	}

	function getIterator() {
		return new ArrayIterator($this->data);
	}

	function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}
	function offsetExists($offset) {
		return isset($this->data[$offset]);
	}
	function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}
}

class AsnBitStr extends AsnSequence {
}

class AsnEnumerated extends AsnSequence {
}

class AsnSet extends AsnSequence {
}

?>
