<?
require_once("BC.php");

$test = array(
		array("11010011", 2, 10),
		array("11010011", 2, 16),
		array("11010011", 10, 2),
		array("11010011", 10, 16),
		array("AFC8", 16, 2),
		array("AFC8", 16, 10)
		);

foreach ($test as $t) {
	echo "n=" . $t[0] . " i=" . $t[1] . " o=" . $t[2] . " conv=" .
		BC::baseConvert($t[0], $t[1], $t[2]) . "\n";
}
?>
