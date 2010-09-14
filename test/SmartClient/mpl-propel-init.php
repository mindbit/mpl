<?
require_once "Env.php";
ENV::setup();

$path = dirname($_SERVER["SCRIPT_FILENAME"]);

// propel init
set_include_path($path . "/model/build/classes" . PATH_SEPARATOR . get_include_path());
require_once ('propel/Propel.php');
Propel::init($path . "/model/build/conf/mpl-conf.php");
// end of propel init
?>
