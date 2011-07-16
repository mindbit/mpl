<?

class BC {
	static function baseConvert($number, $iBase, $oBase) {
		// if iBase != 10, convert to base 10
		if ($iBase != 10) {
			$pow = "1";
			$dec = "0";
			$number = strtoupper($number);
			for ($i = strlen($number) - 1; $i >= 0; $i--) {
				$c = $number[$i];
				if ($c >= 'A')
					$c = (string)(ord($c) - 55);
				$dec = bcadd($dec, bcmul($pow, $c));
				$pow = bcmul($pow, $iBase);
			}
			$number = $dec;
		}
		if ($oBase == 10)
			return $number;
		$ret = '';
		while (bccomp($number, "0") > 0) {
			$mod = bcmod($number, $oBase);
			$number = bcdiv(bcsub($number, $mod), $oBase);
			if ((int)$mod >= 10)
				$mod = chr(55 + $mod);
			$ret = $mod . $ret;
		}
		return $ret;
	}
}

?>
