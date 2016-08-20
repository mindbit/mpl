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

class HTTP
{
    public static function inVar($varName, $default = null, $type = null)
    {
        if (!isset($_REQUEST[$varName])) {
            return $default;
        }
        if ($type === null) {
            return $_REQUEST[$varName];
        }
        $ret = $_REQUEST[$varName];
        settype($ret, $type);
        return $ret;
    }

    public static function rawRequest($includeRequest = true, $includeHeaders = true)
    {
        $hdr = "";
        if ($includeRequest) {
            $hdr .=
                $_SERVER['REQUEST_METHOD'] . ' ' .
                $_SERVER['REQUEST_URI'] .
                "\r\n";
        }
        if ($includeHeaders) {
            $hdrArray = apache_request_headers();
            foreach ($hdrArray as $name => $value) {
                $hdr .= $name . ": " . $value . "\r\n";
            }
            $hdr .= "\r\n";
        }
        return $hdr . file_get_contents('php://input');
    }
}
