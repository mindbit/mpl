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

namespace Mindbit\Mpl\Pki;

abstract class GenericKey
{
    protected $pem;
    protected $data;

    const FORMAT_BASE64        = 1;
    const FORMAT_DER           = 2;
    const FORMAT_PEM           = 3;
    const FORMAT_DATA          = 4;

    public function __construct($pKey, $format = self::FORMAT_PEM)
    {
        switch ($format) {
            case self::FORMAT_DATA:
                $this->data = $pKey;
                $this->pem = $pKey['key'];
                break;
            case self::FORMAT_PEM:
                $this->pem = $pKey;
                break;
            default:
                throw new Exception("FIXME");
        }
    }

    public function __toString()
    {
        return $this->pem;
    }

    abstract public function parse();

    protected static function keyTypeString($keyType)
    {
        switch ($keyType) {
            case OPENSSL_KEYTYPE_RSA:
                return 'RSA';
            case OPENSSL_KEYTYPE_DSA:
                return 'DSA';
            case OPENSSL_KEYTYPE_DH:
                return 'DH';
            case OPENSSL_KEYTYPE_EC:
                return 'EC';
        }
    }

    public function getData()
    {
        $this->parse();
        return $this->data;
    }

    public function getInfo()
    {
        $this->parse();
        $ret = array(
                "Type"        => self::keyTypeString($this->data['type']),
                "Length"      => $this->data['bits'],
                "PEM"         => $this->data['key']
                );
        return $ret;
    }
}
