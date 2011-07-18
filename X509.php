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

	function bcDecHex($dec) {
		$hex = BC::baseConvert($dec, 10, 16);
		if (strlen($hex) % 2)
			$hex = "0" . $hex;
		return implode(':', str_split($hex, 2));
	}
}

?>
