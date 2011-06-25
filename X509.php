<?

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

	function base64toPEM($data) {
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
		$glue = "";
		$ret = "";
		while (strlen($dec) > 2) {
			$mod = bcmod($dec, 256);
			$ret = dechex((int)$mod) . $glue . $ret;
			$glue = ":";
			$dec = bcdiv(bcsub($dec, $mod), "256");
		}
		if ((int)$dec > 0 || $glue == "")
			$ret = dechex((int)$dec) . $glue . $ret;
		return $ret;
	}
}

?>
