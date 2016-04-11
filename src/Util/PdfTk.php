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

namespace Mindbit\Mpl\Util;

class PdfTk {
	static function fdfUtf16($text, $encoding = "utf-8") {
		$utf16 = iconv($encoding, "utf-16be", $text);
		$len = strlen($utf16);
		for ($i = 0; $i < $len; $i += 2) {
			if (!ord($utf16[$i]) && ord($utf16[$i + 1]) <= 127)
				continue;
			return chr(0xfe) . chr(0xff) . $utf16 .
				chr(0) . chr(0);
		}
		return iconv("utf-16be", "ascii", $utf16);
	}

	static function fdf($data, $encoding = "utf-8") {
		$search = array('\\', '(', ')');
		$replace = array('\\\\', '\(', '\)');
		$ret = "%FDF-1.2\n%" . chr(0xe2) . chr(0xe3) . chr(0xcf) . chr(0xd3) . "\n1 0 obj\n<</FDF<</Fields[";
		foreach ($data as $t => $v) {
			$t = str_replace($search, $replace, self::fdfUtf16($t, $encoding));
			$v = str_replace($search, $replace, self::fdfUtf16($v, $encoding));
			$ret .= "<</V(" . $v . ")" ."/T(" . $t . ")>>";
		}
		$ret .= "]>>>>\nendobj\ntrailer\n<</Root 1 0 R>>\n%%EOF\n";
		return $ret;
	}

	static function fillForm($input, $output, $data, $encoding = "utf-8", $flatten = false) {
		$fdf = self::fdf($data, $encoding);
		$cmd = "pdftk " . escapeshellarg($input) . " fill_form - output " .
			escapeshellarg($output);
		if ($flatten)
			$cmd .= " flatten";
		$descriptorspec = array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w")
				);

		$process = proc_open($cmd, $descriptorspec, $pipes);
		if (!is_resource($process))
			throw new Exception("proc_open failed");

		fwrite($pipes[0], $fdf);
		fclose($pipes[0]);

		$out = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		return proc_close($process);
	}
}
