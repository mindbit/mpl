<?
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
