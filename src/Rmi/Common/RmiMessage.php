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

namespace Mindbit\Mpl\Rmi\Common;

/**
 * Support for reading from and writing to streams.
 */
abstract class RmiMessage
{
    abstract public function serialize();

    /**
     * Safely unserialize php variables.
     */
    protected static function safeUnserialize($str)
    {
        $oldIni = ini_get('unserialize_callback_func');
        ini_set('unserialize_callback_func', '');
        $errorHandler = ErrorHandler::getHandler();
        ErrorHandler::setHandler(new ThrowErrorHandler());
        $e = null;
        try {
            $ret = unserialize($str);
        } catch (Exception $_e) {
            $e = $_e;
        }
        ErrorHandler::setHandler($errorHandler);
        ini_set('unserialize_callback_func', $oldIni);
        if ($e !== null) {
            throw $e;
        }
        return $ret;
    }

    protected static function read($stream)
    {
        // even an empty RmiMessage object would be serialized as
        // "O:10:"RmiMessage":0:{}" (20 characters) so reading in chunks
        // of 16 bytes guarantees that we don't read beyond the end of
        // the serialized message
        for ($buf = ""; strlen($buf) < 24; $buf .= $chunk) {
            $chunk = fread($stream, 24);
            if ($chunk === false) {
                throw new Exception("read failed");
            }
            if (!strlen($chunk)) { // the other end has closed
                return null;
            }
        }
        if (strpos($buf, "O:16:\"RmiMessageHeader\"") !== 0) {
            // since the protocol is out-of-sync anyway, try to read
            // as much as possible and give the user more info
            $read = array($stream);
            $write = array();
            $except = array();
            stream_select($read, $write, $except, 0, 0);
            if (!empty($read)) {
                $chunk = fread($stream, 8192);
                $buf .= $chunk;
            }
            throw new Exception("data format error: " . $buf);
        }
        $chunk = $buf;
        $pos = 0;
        while (($_pos = strpos($chunk, '}')) === false) {
            $pos += strlen($chunk);
            $chunk = fread($stream, 16);
            if ($chunk === false) {
                throw new Exception("read failed");
            }
            $buf .= $chunk;
        }
        $pos += $_pos;
        $hdr = unserialize(substr($buf, 0, ++$pos));
        $buf = substr($buf, $pos);
        if (!($hdr instanceof RmiMessageHeader)) {
            throw new Exception("oops! we fished an alien");
        }
        $dataLength = $hdr->getDataLength();
        while (($need = $dataLength - strlen($buf)) > 0) {
            $chunk = fread($stream, $need);
            if ($chunk === false) {
                throw new Exception("read failed");
            }
            $buf .= $chunk;
        }
        $ret = self::safeUnserialize($buf);
        if (!($ret instanceof RmiMessage)) {
            throw new Exception("oops! we fished an alien");
        }
        return $ret;
    }

    public function write($stream)
    {
        $data = $this->serialize();
        $hdr = new RmiMessageHeader(strlen($data));
        $data = serialize($hdr) . $data;
        for ($written = 0; $written < strlen($data); $written += $fwrite) {
            $fwrite = fwrite($stream, substr($data, $written));
            if ($fwrite === false) {
                throw new Exception("write failed");
            }
            if (!$fwrite) {
                throw new Exception("broken pipe");
            }
        }
        fflush($stream);
    }
}
