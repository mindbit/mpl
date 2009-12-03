<?

class Stream {
	protected $stream;

	/* Just as a reminder:
	   Big Endian    = MSB at lowest address
	   Little Endian = LSB at lowest address
	 */

	function __construct($stream = null) {
		$this->stream = $stream;
	}

	function readIntBE() {
	}

	function readIntLE() {
		if (strlen($s = fread($this->stream, 4)) < 4)
			return null;

		$x = 0;

		for ($i = 3; $i >= 0; $i--)
			$x = ($x << 8) | ord($s[$i]);

		return $x;
	}

	function readByte() {
		if (($c = fgetc($this->stream)) === false)
			return null;
		return ord($c);
	}

	function seek($offset, $whence = SEEK_SET) {
		return fseek($this->stream, $offset, $whence);
	}

	function read($length = 4096) {
		return fread($this->stream, $length);
	}
}

?>
