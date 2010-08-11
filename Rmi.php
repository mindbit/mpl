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
		for ($buf = ""; strlen($buf) < 24; $buf .= $chunk) {
			$chunk = fread($stream, 24);
			if ($chunk === false)
				throw new Exception("read failed");
			if (!strlen($chunk))
				throw new Exception("premature end of data");
		}
		if (strpos($buf, "O:16:\"RmiMessageHeader\"") !== 0) { var_dump($buf);
			throw new Exception("data format error");}
		$chunk = $buf;
		$pos = 0;
		while (($_pos = strpos($chunk, '}')) === false) {
			$pos += strlen($chunk);
			$chunk = fread($stream, 16);
			if ($chunk === false)
				throw new Exception("read failed");
			$buf .= $chunk;
		}
		$pos += $_pos;
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
		if (!is_a($ret, "RmiMessage"))
			throw new Exception("oops! we fished an alien");
		return $ret;
	}

	function write($stream) {
		$data = $this->serialize();
		$hdr = new RmiMessageHeader(strlen($data));
		$data = serialize($hdr) . $data;
		for ($written = 0; $written < strlen($data); $written += $fwrite) {
			$fwrite = fwrite($stream, substr($data, $written));
			if ($fwrite === false)
				throw new Exception("write failed");
		}
		fflush($stream);
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

	function serialize() {
		// TODO this must make any client-to-server conversions
		// prior to the real serialization
		return serialize($this);
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
		$instance = $refClass->getConstructor() === null ?
			$refClass->newInstance() :
			$refClass->newInstanceArgs($this->args);
		return new RmiNewInstanceResponse(RmiServer::registerObject($instance), null);
	}
}

class RmiCallMethodRequest extends RmiBaseRequest {
	protected $rmiId;
	protected $method;

	function __construct($rmiId, $method, $args) {
		$this->rmiId = $rmiId;
		$this->method = $method;
		$this->args = $args;
	}

	function process() {
		$instance = RmiServer::getObject($this->rmiId);
		$refObject = new ReflectionObject($instance);
		$ret = call_user_func_array(array($instance, $this->method), $this->args);
		return new RmiCallMethodResponse($ret, null);
	}
}

abstract class RmiConnector {
	protected $streamIn, $streamOut;
}

abstract class RmiClient extends RmiConnector {
	function createInstance($class) {
		$args = func_get_args();
		array_shift($args);
		$msg = new RmiNewInstanceRequest($class, $args);
		$msg->write($this->streamOut);
		$response = RmiMessage::read($this->streamIn);
		assert(is_a($response, "RmiNewInstanceResponse"));
		$instance = new RmiStub();
		$instance->setRmiId($response->getRmiId());
		$instance->setRmiClient($this);
		return $instance;
	}

	function callMethod($object, $method, $args) {
		$msg = New RmiCallMethodRequest($object->getRmiId(), $method, $args);
		$msg->write($this->streamOut);
		$response = RmiMessage::read($this->streamIn);
		assert(is_a($response, "RmiCallMethodResponse"));
		return $response->getRetVal();
	}
}

class ProcOpenRmiClient extends RmiClient {
	protected $process;
	protected $streamErr;

	function __construct($cmd, $stderr = array("file", "/dev/null", "a")) {
		$descriptorspec = array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => $stderr);
		$this->process = proc_open($cmd, $descriptorspec, $pipes);
		assert(is_resource($this->process));
		$this->streamIn = $pipes[1];
		$this->streamOut = $pipes[0];
		if (isset($pipes[2]))
			$this->streamErr = $pipes[2];
	}
}

abstract class RmiServer extends RmiConnector {
	protected static $registry = array();
	protected static $serial = 0;

	static function uniqid() {
		return uniqid(sprintf("%04x%04x.", mt_rand() & 0xffff, ++self::$serial & 0xffff), true);
	}

	static function registerObject($object) {
		assert(is_object($object));
		$array = (array)$object;
		if (isset($array["__rmiId"]))
			return $array["__rmiId"];
		$object->__rmiId = self::uniqid();
		self::$registry[$object->__rmiId] = $object;
		return $object->__rmiId;
	}

	static function getObject($rmiId) {
		return isset(self::$registry[$rmiId]) ?
			self::$registry[$rmiId] : null;
	}

	function run() {
		set_time_limit(0);
		do {
			$msg = RmiMessage::read($this->streamIn);
			assert(is_a($msg, "RmiRequest"));
			$response = $msg->process();
			if ($response !== null)
				$response->write($this->streamOut);
		} while ($response !== null);
	}
}

class StdInOutRmiServer extends RmiServer {
	function __construct() {
		$this->streamIn = fopen("php://stdin", "r");
		$this->streamOut = fopen("php://stdout", "w");
	}
}

abstract class RmiResponse extends RmiMessage {
}

abstract class RmiBaseResponse extends RmiResponse {
	/**
	 * Arguments that have been changed from within the
	 * called function (constructor or method).
	 */
	protected $args;

	function __construct($args) {
		$this->args = $args;
	}

	function serialize() {
		// TODO this must make any server-to-client conversions
		// prior to the real serialization
		return serialize($this);
	}
}

class RmiNewInstanceResponse extends RmiBaseResponse {
	protected $rmiId;

	function __construct($rmiId, $args) {
		$this->rmiId = $rmiId;
		$this->args = $args;
	}

	function getRmiId() {
		return $this->rmiId;
	}
}

class RmiCallMethodResponse extends RmiBaseResponse {
	protected $retVal;

	function __construct($retVal, $args) {
		$this->retVal = $retVal;
		$this->args = $args;
	}

	function getRetVal() {
		return $this->retVal;
	}
}

class RmiStub {
	protected $__rmiId;
	protected $__rmiClient;

	function getRmiId() {
		return $this->__rmiId;
	}

	function setRmiId($rmiId) {
		$this->__rmiId = $rmiId;
	}

	function getRmiClient() {
		return $this->__rmiClient;
	}

	function setRmiClient($rmiClient) {
		$this->__rmiClient = $rmiClient;
	}

	function __call($name, $arguments) {
		return $this->__rmiClient->callMethod($this, $name, $arguments);
	}
}

?>
