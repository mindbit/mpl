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

/*
 * This class is based on asn1.php from the Mistpark project.
 */

class Asn
{
    // Tag classes (bits 7 and 6)
    const CLASS_MASK        = 0xC0;
    const C_UNIVERSAL       = 0x00;
    const C_APPLICATION     = 0x40;
    const C_CONTEXT         = 0x80;
    const C_PRIVATE         = 0xC0;

    // Encoding type (bit 5)
    const FORM_MASK         = 0x20;
    const F_PRIMITIVE       = 0x00;
    const F_CONSTRUCTED     = 0x20;

    // Universal tags
    const TAG_MASK          = 0x1F;
    const T_BOOLEAN         = 1;
    const T_INTEGER         = 2;
    const T_BIT_STR         = 3;
    const T_OCTET_STR       = 4;
    const T_NULL            = 5;
    const T_OBJECT_ID       = 6;
    const T_REAL            = 9;
    const T_ENUMERATED      = 10;
    const T_UTF8_STRING     = 12;
    const T_RELATIVE_OID    = 13;
    const T_PRINT_STR       = 19;
    const T_IA5_STR         = 22;
    const T_UTC_TIME        = 23;
    const T_GENERAL_TIME    = 24;
    const T_SEQUENCE        = 48;
    const T_SET             = 49;

    // Length encoding
    const M_LONG_LEN        = 0x80;
    const M_BIT             = 0x80;

    public static function getClassMap()
    {
        return array(
                self::T_BOOLEAN         => "AsnBoolean",
                self::T_INTEGER         => "AsnInteger",
                self::T_BIT_STR         => "AsnBitString",
                self::T_OCTET_STR       => "AsnOctetString",
                self::T_NULL            => "AsnNull",
                self::T_OBJECT_ID       => "AsnObjectId",
                self::T_REAL            => "AsnReal",
                self::T_ENUMERATED      => "AsnEnumerated",
                self::T_UTF8_STRING     => "AsnUTF8String",
                self::T_RELATIVE_OID    => "AsnRelativeOid",
                self::T_PRINT_STR       => "AsnPrintString",
                self::T_IA5_STR         => "AsnIa5String",
                self::T_UTC_TIME        => "AsnUtcTime",
                self::T_GENERAL_TIME    => "AsnGeneralTime",
                self::T_SEQUENCE        => "AsnSequence",
                self::T_SET             => "AsnSet"
                );
    }

    public static function newInstance($type)
    {
        $classMap = self::getClassMap();
        if (!isset($classMap[$type])) {
            return new AsnUnknown($type);
        }
        $class = $classMap[$type];
        return new $class;
    }

    public static function parse($string)
    {
        $ret = new AsnSequence();
        $ret->parse($string);
        return $ret;
    }
}
