<?php
/*
 * Mindbit PHP Library
 * Copyright (C) 2009 Mindbit SRL
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of version 2.1 of the GNU Lesser General Public
 * License as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

/* It is extremely important to explicitly specify the scale for all bc*
   functions. Otherwise, they will use the existing global setting (that
   can be changed using bcscale()). For instance, in an Apache httpd
   prefork mpm, there is no guarantee about the initial value seen by
   php. In fact, it may inherit the default value set by previous
   requests served by the same process.
 */

namespace Mindbit\Mpl\Util;

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
				$dec = bcadd($dec, bcmul($pow, $c, 0), 0);
				$pow = bcmul($pow, $iBase, 0);
			}
			$number = $dec;
		}
		if ($oBase == 10)
			return $number;
		$ret = '';
		while (bccomp($number, "0", 0) > 0) {
			$mod = bcmod($number, $oBase);
			$number = bcdiv(bcsub($number, $mod, 0), $oBase, 0);
			if ((int)$mod >= 10)
				$mod = chr(55 + $mod);
			$ret = $mod . $ret;
		}
		return $ret;
	}
}
