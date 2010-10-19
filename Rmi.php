<?
require_once "ThrowErrorHandler.php";

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

	/**
	 * Safely unserialize php variables.
	 */
	static function safeUnserialize($str) {
		$oldIni = ini_get('unserialize_callback_func');
		ini_set('unserialize_callback_func', '');
		$errorHandler = ErrorHandler::getHandler();
		ErrorHandler::setHandler(new ThrowErrorHandler());
		$e = null;
		try {
			$ret = unserialize($str);
		} catch (Exception $_e) {
			$e = $_e;
		}
		ErrorHandler::setHandler($errorHandler);
		ini_set('unserialize_callback_func', $oldIni);
		if ($e !== null)
			throw $e;
		return $ret;
	}

	static function read($stream) {
		// even an empty RmiMessage object would be serialized as
		// "O:10:"RmiMessage":0:{}" (20 characters) so reading in chunks
		// of 16 bytes guarantees that we don't read beyond the end of
		// the serialized message
		for ($buf = ""; strlen($buf) < 24; $buf .= $chunk) {
			$chunk = fread($stream, 24);
			if ($chunk === false)
				throw new Exception("read failed");
			if (!strlen($chunk)) // the other end has closed
				return null;
		}
		if (strpos($buf, "O:16:\"RmiMessageHeader\"") !== 0) {
			// since the protocol is out-of-sync anyway, try to read
			// as much as possible and give the user more info
			$read = array($stream);
			$write = array();
			$except = array();
			stream_select($read, $write, $except, 0, 0);
			if (!empty($read)) {
				$chunk = fread($stream, 8192);
				$buf .= $chunk;
			}
			throw new Exception("data format error: " . $buf);
		}
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
		if (!($hdr instanceof RmiMessageHeader))
			throw new Exception("oops! we fished an alien");
		$dataLength = $hdr->getDataLength();
		while (($need = $dataLength - strlen($buf)) > 0) {
			$chunk = fread($stream, $need);
			if ($chunk === false)
				throw new Exception("read failed");
			$buf .= $chunk;
		}
		$ret = self::safeUnserialize($buf);
		if (!($ret instanceof RmiMessage))
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
			if (!$fwrite)
				throw new Exception("broken pipe");
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
		try {
			$instance = $refClass->getConstructor() === null ?
				$refClass->newInstance() :
				$refClass->newInstanceArgs($this->args);
		} catch (Exception $e) {
			return new RmiExceptionResponse($e);
		}
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
		try {
			$ret = call_user_func_array(array($instance, $this->method), $this->args);
		} catch (Exception $e) {
			return new RmiExceptionResponse($e);
		}
		return new RmiCallMethodResponse($ret, null);
	}
}

abstract class RmiConnector {
	protected $streamIn, $streamOut;

	function getStreamIn() {
		return $this->streamIn;
	}

	function getStreamOut() {
		return $this->streamOut;
	}
}

abstract class RmiClient extends RmiConnector {
	function createInstance($class) {
		$args = func_get_args();
		array_shift($args);
		$msg = $this->dispatch(new RmiNewInstanceRequest($class, $args));
		assert($msg instanceof RmiNewInstanceResponse);
		$instance = new RmiStub();
		$instance->setRmiId($msg->getRmiId());
		$instance->setRmiClient($this);
		return $instance;
	}

	function callMethod($object, $method, $args) {
		$msg = $this->dispatch(new RmiCallMethodRequest($object->getRmiId(), $method, $args));
		assert($msg instanceof RmiCallMethodResponse);
		return $msg->getRetVal();
	}

	function dispatch($request) {
		$request->write($this->streamOut);
		$response = RmiMessage::read($this->streamIn);
		if ($response === null)
			throw new Exception("Unexpected end of stream");
		assert($response instanceof RmiResponse);
		if ($response instanceof RmiExceptionResponse)
			$this->handleRemoteException($response->getException());
		return $response;
	}

	function handleRemoteException($e) {
		throw new RemoteException($e);
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
	protected static $serverInstance;

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

	final function run() {
		self::$serverInstance = $this;
		set_time_limit(0);
		ErrorHandler::setHandler(new RmiServerErrorHandler());
		do {
			$msg = RmiMessage::read($this->streamIn);
			if ($msg === null)
				break;
			assert($msg instanceof RmiRequest);
			$response = $msg->process();
			if ($response !== null)
				$response->write($this->streamOut);
		} while ($response !== null);
	}

	static function getInstance() {
		return self::$serverInstance;
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

class RmiExceptionResponse extends RmiResponse {
	protected $exception;

	function __construct($exception) {
		$this->exception = $exception;
	}

	function getException() {
		return $this->exception;
	}

	function serialize() {
		return serialize($this);
	}
}

class RmiFatalExceptionResponse extends RmiExceptionResponse {
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

class RemoteException extends Exception {
	function __construct($e) {
		parent::__construct("Exception in RMI server", 0, $e);
	}
}

class RmiServerErrorHandler extends ThrowErrorHandler {
	protected function __handleException($exception) {
		// For unknown reasons, we have an uncaught exception (it
		// probably happened outside the real-object call code).
		// As a last resort, try to respond with an
		// RmiFatalExceptionResponse, since we are going to die
		// anyway.
		$msg = new RmiFatalExceptionResponse($exception);
		$msg->write(RmiServer::getInstance()->getStreamOut());
	}
}
?>
