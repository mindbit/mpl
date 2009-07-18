<?

require_once("ErrorHandler.php");
require_once("GenericErrorHandler.php");

class Env {
	static function get($constant, $default = null) {
		return defined($constant) ? constant($constant) : $default;
	}

	static function setAssertOptions() {
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_BAIL, 0);
		assert_options(ASSERT_QUIET_EVAL, 0);
		assert_options(ASSERT_CALLBACK, 'lEXC_assertHandler');
		// FIXME handlerul trebuie sa fie apel static catre o metoda din ErrorHandler
	}

	static function setup() {
		error_reporting(E_PHPINTERNAL);
		ErrorHandler::setHandler(new GenericErrorHandler());
		ErrorHandler::setMask(Env::get("MPL_ERROR_MASK", E_NONE));
		set_error_handler(array("ErrorHandler", "handle"));
		// Env::setAssertOptions();
	}
}

?>
