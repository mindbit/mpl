<?

require_once "GenericUser.php";

class ExampleUser implements GenericUser {
	protected $realName;
	protected $username;

	static function authenticate($username, $password) {
		if ($username != "admin" || $password != "1234")
			return null;
		$ret = new ExampleUser();
		$ret->realName = "Example Admin";
		$ret->username = "admin";
		return $ret;
	}

	function getRealName() {
		return $this->realName;
	}

	function getUsername() {
		return $this->username;
	}
}

?>
