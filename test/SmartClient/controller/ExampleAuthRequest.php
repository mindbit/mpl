<?

require_once "model/ExampleUser.php";
require_once "AuthRequest.php";

class ExampleAuthRequest extends AuthRequest {
	function authenticateUser($username, $password) {
		return ExampleUser::authenticate($username, $password);
	}
}

?>
