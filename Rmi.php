<?

class RmiMessageHeader {
	protected $version = 1;
	protected $dataLength;

	function __construct($dataLen) {
		$this->dataLength = $dataLen;
	}

	function getDataLength() {
		return $this->dataLength;
	}
}

/**
 * Support for reading from and writing to streams.
 */
abstract class RmiMessage {
	abstract function serialize();

	static function read($stream) {
		// even an empty RmiMessage object would be serialized as
		// "O:10:"RmiMessage":0:{}" (20 characters) so reading in chunks
		// of 16 bytes guarantees that we don't read beyond the end of
		// the serialized message
		$buf = fread($stream, 16);
		if ($buf === false)
			throw new Exception("read failed");
		if (!strlen($buf))
			throw new Exception("premature end of data");
		if ($buf[0] != '{')
			throw new Exception("data format error");
		$chunk = $buf;
		while (($pos = strpos($chunk, '}')) === false) {
			$chunk = fread($stream, 16);
			if ($chunk === false)
				throw new Exception("read failed");
			$buf .= $chunk;
		}
		$hdr = unserialize(substr($buf, 0, ++$pos));
		$buf = substr($buf, $pos);
		if (!is_a($hdr, "RmiMessageHeader"))
			throw new Exception("oops! we fished an alien");
		$dataLength = $hdr->getDataLength();
		while (($need = $dataLength - strlen($buf)) > 0) {
			$chunk = fread($stream, $need);
			if ($chunk === false)
				throw new Exception("read failed");
			$buf .= $chunk;
		}
		$ret = unserialize($buf);
		if (!is_a($hdr, "RmiMessage"))
			throw new Exception("oops! we fished an alien");
		return $ret;
	}

	function write($stream) {
		$data = $this->serialize();
		$hdr = new RmiMessageHeader(strlen($data));
		$data = serialize($hdr) . $data;
		for ($written = 0; $written < strlen($data); $written += $fwrite) {
			$fwrite = fwrite($fp, substr($string, $written));
			if ($fwrite === false)
				throw new Exception("write failed");
		}
	}
}

abstract class RmiRequest extends RmiMessage {
	abstract function process();
}

abstract class RmiBaseRequest extends RmiRequest {
	protected $args;

	function __construct($args) {
		$this->args = $args;
	}
}

class RmiNewInstanceRequest extends RmiBaseRequest {
	protected $class;

	function __construct($class, $args) {
		$this->class = $class;
		$this->args = $args;
	}

	function process() {
		$refClass = new ReflectionClass($this->class);
		$instance = $refClass->newInstanceArgs($this->args);
		RmiServer::register($instance);
		return new RmiNewInstanceResponse($instance, null);
	}
}

class RmiCallMethodRequest extends RmiBaseRequest {
	protected $object;
	protected $method;

	function __construct($object, $method, $args) {
		$this->object = $object;
		$this->method = $method;
		$this->args = $args;
	}

	function process() {
		$instance = RmiServer::getObject($this->object);
		$refObject = new ReflectionObject($instance);
		$ret = call_user_func_array(array($instance, $this->method), $this->args);
		return new RmiCallMethodResponse($ret, null);
	}
}

class RmiServer {
	protected static $registry = array();

	static function uniqid() {
		return uniqid(sprintf("%08x", mt_rand()), true);
	}

	static function registerObject($object) {
		assert(is_object($object));
		$array = (array)$object;
		assert(!isset[$array["__rmi"]]);
		$object->__rmi = self::uniqid();
		self::$registry[$object->__rmi] = $object;
	}

	function run() {
	}
}

abstract class RmiResponse extends RmiMessage {
}

abstract class RmiBaseResponse extends RmiRequest {
	/**
	 * Arguments that have been changed from within the
	 * called function (constructor or method).
	 */
	protected $args;

	function __construct($args) {
		$this->args = $args;
	}
}

class RmiNewInstanceResponse extends RmiBaseResponse {
	protected $instance;

	function __construct($instance, $args) {
		$this->instance = $instance;
		$this->args = $args;
	}
}

class RmiCallMethodResponse extends RmiBaseResponse {
	protected $instance;

	function __construct($instance, $args) {
		$this->instance = $instance;
		$this->args = $args;
	}
}

?>
