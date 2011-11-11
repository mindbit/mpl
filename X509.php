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

require_once "BC.php";
require_once "Asn.php";

class X509 {
	protected $pem;
	protected $data;

	const FORMAT_BASE64		= 1;
	const FORMAT_DER		= 2;
	const FORMAT_PEM		= 3;

	function __construct($x509, $format = self::FORMAT_BASE64) {
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

	static function base64toPEM($data) {
		return
			"-----BEGIN CERTIFICATE-----\n" .
			chunk_split($data, 64, "\n") .
			"-----END CERTIFICATE-----\n";
	}

	function parse() {
		if ($this->data !== null)
			return;
		$this->data = openssl_x509_parse($this->pem);
	}

	static function pemToDer($pem)
	{
		$split = explode("\n",$pem);
		$stare = 0;
		$result="";
		foreach ($split as $line)
		{
			switch ($stare)
			{
			case 0:
				if (substr($line,0,2) != "--")
					continue;
				$stare = 1;
				break;
			case 1:
				if (substr($line,0,2) != "--")
				{ 
					$result.= trim($line); 
					continue;
				}
				return base64_decode($result);
				break;
			}
		}
	}

	function getDer()
	{
		return $this->pemToDer($this->pem);
	}
	
	function getPem()
	{
		return $this->pem;
	}

	function getData() {
		$this->parse();
		return $this->data;
	}

	function glueFields($fields, $glue = ",") {
		$ret = "";
		$_glue = "";
		foreach ($fields as $k => $v) {
			$ret .= $_glue . $k . "=" . $v;
			$_glue = $glue;
		}
		return $ret;
	}

	static function bcDecHex($dec, $glue = ':') {
		$hex = BC::baseConvert($dec, 10, 16);
		if (strlen($hex) % 2)
			$hex = "0" . $hex;
		return implode($glue, str_split($hex, 2));
	}

	static function bcHexDec($hex, $split = ':') {
		$hex = str_replace($split,'',$hex);
		return BC::baseConvert($hex, 16, 10);
	}

	function buildQcOidMap() {
		$rfc3739 = new RFC3739QcOid();
		$etsi = new EtsiQcOid();
		return array_merge($rfc3739->getOidMap(), $etsi->getOidMap());
	}

	function parseQcOid($oid) {
		$map = $this->buildQcOidMap();
		return (isset($map[$oid]) ? $map[$oid] : "OID") .
			" [" . $oid . "]";
	}

	function parseQcStatements() {
		$this->parse();
		if (!isset($this->data["extensions"]))
			return null;
		if (!isset($this->data["extensions"]["qcStatements"]))
			return null;
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
}

class RFC3739QcOid {
	function __construct() {
		$this->qcs = new AsnObjectId("1.3.6.1.5.5.7.11");
		$this->qcsPkixQcSyntaxV1 = $this->qcs->branch("1");
		$this->qcsPkixQcSyntaxV2 = $this->qcs->branch("2");
	}

	function getOidMap() {
		return array(
				$this->qcsPkixQcSyntaxV1->getData() => "PKIX QCSyntax-v1",
				$this->qcsPkixQcSyntaxV2->getData() => "PKIX QCSyntax-v2"
				);
	}
}

class EtsiQcOid {
	function __construct() {
		$this->etsiQcs = new AsnObjectId("0.4.0.1862.1");
		$this->etsiQcsQcCompliance = $this->etsiQcs->branch("1");
		$this->etsiQcsLimitValue = $this->etsiQcs->branch("2");
		$this->etsiQcsRetentionPeriod = $this->etsiQcs->branch("3");
		$this->etsiQcsQcSSCD = $this->etsiQcs->branch("4");
	}

	function getOidMap() {
		return array(
				$this->etsiQcsQcCompliance->getData() => "ETSI QC Compliance",
				$this->etsiQcsLimitValue->getData() => "ETSI Transaction Value Limit",
				$this->etsiQcsRetentionPeriod->getData() => "ETSI Retention Period",
				$this->etsiQcsQcSSCD->getData() => "ETSI Secure Signature Creation Device"
				);
	}
}

?>
