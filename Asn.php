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
	// Tag classes (bits 7 and 6)
	const CLASS_MASK		= 0xC0;
	const C_UNIVERSAL		= 0x00;
	const C_APPLICATION		= 0x40;
	const C_CONTEXT			= 0x80;
	const C_PRIVATE			= 0xC0;

	// Encoding type (bit 5)
	const FORM_MASK			= 0x20;
	const F_PRIMITIVE		= 0x00;
	const F_CONSTRUCTED		= 0x20;

	// Universal tags
	const TAG_MASK			= 0x1F;
	const T_BOOLEAN			= 1;
	const T_INTEGER			= 2;
	const T_BIT_STR			= 3;
	const T_OCTET_STR		= 4;
	const T_NULL			= 5;
	const T_OBJECT_ID		= 6;
	const T_REAL			= 9;
	const T_ENUMERATED		= 10;
	const T_UTF8_STRING		= 12;
	const T_RELATIVE_OID	= 13;
	const T_PRINT_STR		= 19;
	const T_IA5_STR			= 22;
	const T_UTC_TIME		= 23;
	const T_GENERAL_TIME	= 24;
	const T_SEQUENCE		= 48;
	const T_SET				= 49;

	// Length encoding
	const M_LONG_LEN		= 0x80;
	const M_BIT				= 0x80;

	static function getClassMap() {
		return array(
				self::T_BOOLEAN			=> "AsnBoolean",
				self::T_INTEGER			=> "AsnInteger",
				self::T_BIT_STR			=> "AsnBitString",
				self::T_OCTET_STR		=> "AsnOctetString",
				self::T_NULL			=> "AsnNull",
				self::T_OBJECT_ID		=> "AsnObjectId",
				self::T_REAL			=> "AsnReal",
				self::T_ENUMERATED		=> "AsnEnumerated",
				self::T_UTF8_STRING		=> "AsnUTF8String",
				self::T_RELATIVE_OID	=> "AsnRelativeOid",
				self::T_PRINT_STR		=> "AsnPrintString",
				self::T_IA5_STR			=> "AsnIa5String",
				self::T_UTC_TIME		=> "AsnUtcTime",
				self::T_GENERAL_TIME	=> "AsnGeneralTime",
				self::T_SEQUENCE		=> "AsnSequence",
				self::T_SET				=> "AsnSet"
				);
	}

	static function newInstance($type) {
		$classMap = self::getClassMap();
		if (!isset($classMap[$type]))
			return new AsnUnknown($type);
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

	function parse($string) {
		$this->data = $string;
	}
}

class AsnUnknown extends AsnBase {
	protected $type;

	function __construct($type) {
		$this->type = $type;
	}

	function getType() {
		return $this->type;
	}
}

class AsnUnparseable extends AsnBase {
}

class AsnBoolean extends AsnBase {
	function parse($string) {
		$this->data = (bool)$string;
	}
}

class AsnInteger extends AsnBase {
	function getBase64() {
		return base64_encode($this->data);
	}
}

class AsnOctetString extends AsnBase {
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
}

class AsnRelativeOid extends AsnBase {
}

class AsnPrintString extends AsnBase {
}

class AsnIa5String extends AsnBase {
}

class AsnUtcTime extends AsnBase {
}

class AsnGeneralTime extends AsnBase {
}

class AsnSequence extends AsnBase implements IteratorAggregate, ArrayAccess {
	function parse($string) {
		//echo get_class($this) . " parsing: " ; var_dump($string);
		$this->data = array();
		$endLength = strlen($string);
		$p = 0;
		while ($p < $endLength) {
			// decode object type
			$start = $p;
			$type = ord($string[$p++]);
			if ($type == 0)
				continue;

			// decode object length
			$length = ord($string[$p++]);
			if ($length & Asn::M_LONG_LEN) {
				$length &= Asn::M_LONG_LEN - 1;
				$tempLength = 0;
				if ($p + $length > $endLength)
					break;
				while ($length--)
					$tempLength = ord($string[$p++]) + ($tempLength * 256);
				$length = $tempLength;
			}

			// decode object contents
			if ($p + $length > $endLength)
				break;
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

class AsnSequenceIndex extends AsnSequence {
	protected $index;

	function __construct($index) {
		$this->index = $index;
	}
}

class AsnBitString extends AsnBase {
}

class AsnUTF8String extends AsnBase {
}

class AsnEnumerated extends AsnSequence {
}

class AsnSet extends AsnSequence {
}

?>
