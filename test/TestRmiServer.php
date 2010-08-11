<?
require_once("Env.php");
Env::setup();

require_once("Rmi.php");

class TestServer {
	function foo($x, $y) {
		return $x + $y;
	}
}

$server = new StdInOutRmiServer();
$server->run();
?>
