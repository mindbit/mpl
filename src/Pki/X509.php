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

use Mindbit\Mpl\Util\BC;
use Mindbit\Mpl\Asn\Asn;

class X509
{
    protected $pem;
    protected $data;
    protected $publicKey;

    const FORMAT_BASE64     = 1;
    const FORMAT_DER        = 2;
    const FORMAT_PEM        = 3;

    public function __construct($x509, $format = self::FORMAT_BASE64)
    {
        switch ($format) {
            case self::FORMAT_BASE64:
                $this->pem = $this->base64toPEM($x509);
                break;
            case self::FORMAT_DER:
                $this->pem = $this->base64toPEM(base64_encode($x509));
                break;
            case self::FORMAT_PEM:
                $this->pem = $x509;
                break;
            default:
                throw new Exception("FIXME");
        }
    }

    protected static function base64toPEM($data)
    {
        $data = strtr($data, array(
                    "\r" => "",
                    "\n" => ""
                    ));
        return
            "-----BEGIN CERTIFICATE-----\n" .
            chunk_split($data, 64, "\n") .
            "-----END CERTIFICATE-----\n";
    }

    public function parse()
    {
        if ($this->data !== null) {
            return;
        }
        $this->data = openssl_x509_parse($this->pem);
    }

    protected static function pemToBase64($pem)
    {
        $pem = explode("\n", $pem);
        $state = 0;
        $ret =  "";
        foreach ($pem as $line) {
            switch ($state) {
                case 0:
                    if (substr($line, 0, 2) != "--") {
                        continue;
                    }
                    $state = 1;
                    break;
                case 1:
                    if (substr($line, 0, 2) != "--") {
                        $ret .= trim($line);
                        continue;
                    }
                    return $ret;
            }
        }
    }

    protected static function pemToDer($pem)
    {
        return base64_decode(self::pemToBase64($pem));
    }

    public function getBase64()
    {
        return $this->pemToBase64($this->pem);
    }

    public function getDer()
    {
        return $this->pemToDer($this->pem);
    }

    public function getPem()
    {
        return $this->pem;
    }

    public function getData()
    {
        $this->parse();
        return $this->data;
    }

    protected static function glueFields($fields, $glue = ",")
    {
        $ret = "";
        $_glue = "";
        foreach ($fields as $k => $v) {
            $ret .= $_glue . $k . "=" . $v;
            $_glue = $glue;
        }
        return $ret;
    }

    protected static function bcDecHex($dec, $glue = ':')
    {
        $hex = BC::baseConvert($dec, 10, 16);
        if (strlen($hex) % 2) {
            $hex = "0" . $hex;
        }
        return implode($glue, str_split($hex, 2));
    }

    protected static function bcHexDec($hex, $split = ':')
    {
        $hex = str_replace($split, '', $hex);
        return BC::baseConvert($hex, 16, 10);
    }

    public function buildQcOidMap()
    {
        $rfc3739 = new RFC3739QcOid();
        $etsi = new EtsiQcOid();
        return array_merge($rfc3739->getOidMap(), $etsi->getOidMap());
    }

    public function parseQcOid($oid)
    {
        $map = $this->buildQcOidMap();
        return (isset($map[$oid]) ? $map[$oid] : "OID") .
            " [" . $oid . "]";
    }

    public function parseQcStatements()
    {
        $this->parse();
        if (!isset($this->data["extensions"])) {
            return null;
        }
        if (!isset($this->data["extensions"]["qcStatements"])) {
            return null;
        }
        $asn = Asn::parse($this->data["extensions"]["qcStatements"]);
        assert($asn[0] instanceof AsnSequence);
        $ret = array();
        foreach ($asn[0] as $qcStatement) {
            assert($qcStatement instanceof AsnSequence);
            assert($qcStatement[0] instanceof AsnObjectId);
            $ret[$this->parseQcOID($qcStatement[0]->getData())] =
                isset($qcStatement[1]) ? $qcStatement[1]->getData : null;
        }
        return $ret;
    }

    public function getPublicKey()
    {
        if ($this->publicKey != null) {
            return $this->publicKey;
        }
        $x509 = openssl_x509_read($this->pem);
        $publicKey = openssl_pkey_get_public($x509);
        $this->publicKey = new PublicKey(openssl_pkey_get_details($publicKey), GenericKey::FORMAT_DATA);
        openssl_pkey_free($publicKey);
        openssl_x509_free($x509);
        return $this->publicKey;
    }

    /**
     * Escape DN field according to RFC 2253.
     */
    protected static function escapeDnField($str)
    {
        if (!strlen($str)) {
            return $str;
        }

        $str = strtr($str, array(
                    ','        => '\\,',
                    '+'        => '\\+',
                    '"'        => '\\"',
                    '\\'       => '\\\\',
                    '<'        => '\\<',
                    '>'        => '\\>',
                    ';'        => '\\;'
                    ));

        if ($str[0] == ' ' || $str[0] == '#') {
            $str = '\\' . $str;
        }

        $len = strlen($str) - 1;
        if ($str[$len] == ' ') {
            $str[$len] = '\\';
            $str .= ' ';
        }

        return $str;
    }
}
