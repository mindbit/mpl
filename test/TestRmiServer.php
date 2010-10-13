<?
require_once("Env.php");
Env::setup();

require_once("Rmi.php");

class Foo {
	protected $bar = 123;
}

class TestServer {
	function foo($x, $y) {
		return $x + $y;
	}

	function doError() {
		$foo = new Foo();
		return $iAmUndefined;
	}
}

$server = new StdInOutRmiServer();
$server->run();
?>
