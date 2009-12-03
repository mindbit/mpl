<?

$__dir = dirname(__FILE__);
$__top = substr($__dir, 0, strlen($__dir) - strlen(strrchr($__dir, '/')));
set_include_path(get_include_path() . PATH_SEPARATOR . $__top);
require_once "Env.php";
Env::setup();

require_once "Locale.php";
require_once "Stream.php";

if ($_SERVER['argc'] <= 1) {
	echo "Usage: " . $_SERVER['argv'][0] . " <file.mo>\n";
	exit;
}

$stream = new Stream(fopen($_SERVER['argv'][1], 'r'));

echo Locale::moToPhp($stream);

?>
