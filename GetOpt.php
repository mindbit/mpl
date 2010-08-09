<?

class GetOpt {
	const S_PC_OPT			= 0;
	const S_PC_HAS_ARG		= 1;
	const S_PC_ARG_MAYBE	= 2;

	const OPT_ARG_NONE		= 0;
	const OPT_ARG_REQUIRED	= 1;
	const OPT_ARG_OPTIONAL	= 2;

	const S_AP_OPT			= 0;
	const S_AP_ARG			= 1;
	const S_AP_ARG_MAYBE	= 2;
	const S_AP_REMAINDER	= 3;

	protected $config;
	protected $remainder;

	function __construct($config) {
		if (is_string($config))
			$config = $this->parseConfig($config);
		$this->config = $config;
	}

	function parseConfig($config) {
		$ret = array();
		$state = self::S_PC_OPT;
		$lastOpt = null;
		for ($i = 0; $i < strlen($config); $i++) {
			switch($state) {
			case self::S_PC_OPT:
				switch ($config[$i]) {
				case ':':
						throw new Exception("missing opt");
				default:
					$lastOpt = $config[$i];
					$state = self::S_PC_HAS_ARG;
				}
				break;
			case self::S_PC_HAS_ARG:
				switch ($config[$i]) {
				case ':':
					$state = self::S_PC_ARG_MAYBE;
					break;
				default:
					$ret[$lastOpt] = self::OPT_ARG_NONE;
					$lastOpt = $config[$i];
				}
				break;
			case self::S_PC_ARG_MAYBE:
				switch ($config[$i]) {
				case ':':
					$ret[$lastOpt] = self::OPT_ARG_OPTIONAL;
					$state = self::S_PC_OPT;
					break;
				default:
					$ret[$lastOpt] = self::OPT_ARG_REQUIRED;
					$lastOpt = $config[$i];
					$state = self::S_PC_HAS_ARG;
				}
				break;
			}
		}
		switch($state) {
		case self::S_PC_HAS_ARG:
			$ret[$lastopt] = self::OPT_ARG_NONE;
			return $ret;
		case self::S_PC_ARG_MAYBE:
			$ret[$lastOpt] = self::OPT_ARG_REQUIRED;
			return $ret;
		}
		return $ret;
	}

	function parseArgs($argv) {
		$this->remainder = array();
		$ret = array();
		$state = self::S_AP_OPT;
		$lastOpt = null;
		foreach ($argv as $opt) {
			switch ($state) {
			case self::S_AP_ARG:
				if ($opt[0] == '-')
					throw new Exception("missing argument for " . $lastOpt);
				$ret[$lastOpt][count($ret[$lastOpt]) - 1] = $opt;
				$state = self::S_AP_OPT;
				break;
			case self::S_AP_ARG_MAYBE:
				$state = self::S_AP_OPT;
				if ($opt[0] != '-') {
					$ret[$lastOpt][count($ret[$lastOpt]) - 1] = $opt;
					break;
				}
				// intentionally fall back to S_AP_OPT
			case self::S_AP_OPT:
				if ($opt == '-') {
					$state = self::S_AP_REMAINDER;
					break;
				}
				if ($opt[0] != '-') {
					$this->remainder[] = $opt;
					break;
				}
				if (strlen($opt) < 2)
					throw new Exception("invalid option");
				if ($opt[1] == '-') {
					// TODO long options code
					break;
				}
				for ($i = 1; $i < strlen($opt); $i++) {
					$o = $opt[$i];
					if (!isset($this->config[$o]))
						throw new Exception("unknown option " . $o);
					if ($state == self::S_AP_ARG)
						throw new Exception("missing arg for option " . $o);
					if ($state == self::S_AP_ARG_MAYBE)
						$state = self::S_AP_OPT;
					switch ($this->config[$o]) {
					case self::OPT_ARG_REQUIRED:
						$state = self::S_AP_ARG;
						break;
					case self::OPT_ARG_MAYBE:
						$state = self::S_AP_ARG_MAYBE;
						break;
					}
					if (!isset($ret[$o]))
						$ret[$o] = array();
					$ret[$o][] = true;
					$lastOpt = $o;
				}
				break;
			case self::S_AP_REMAINDER:
				$this->remainder[] = $opt;
				break;
			}
		}
		if ($state == self::S_AP_ARG)
			throw new Exception("missing argument for " . $lastOpt);
		return $ret;
	}
}

?>
