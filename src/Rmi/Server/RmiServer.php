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

namespace Mindbit\Mpl\Rmi\Server;

use Mindbit\Mpl\Rmi\Common\RmiConnector;

abstract class RmiServer extends RmiConnector
{
    protected static $registry = array();
    protected static $serial = 0;
    protected static $serverInstance;

    public static function uniqid()
    {
        return uniqid(sprintf("%04x%04x.", mt_rand() & 0xffff, ++self::$serial & 0xffff), true);
    }

    public static function registerObject($object)
    {
        assert(is_object($object));
        $array = (array)$object;
        if (isset($array["__rmiId"])) {
            return $array["__rmiId"];
        }
            $object->__rmiId = self::uniqid();
            self::$registry[$object->__rmiId] = $object;
            return $object->__rmiId;
    }

    public static function getObject($rmiId)
    {
        return isset(self::$registry[$rmiId]) ?
        self::$registry[$rmiId] : null;
    }

    final public function run()
    {
        self::$serverInstance = $this;
        set_time_limit(0);
        ErrorHandler::setHandler(new RmiServerErrorHandler());
        do {
            $msg = RmiMessage::read($this->streamIn);
            if ($msg === null) {
                break;
            }
            assert($msg instanceof RmiRequest);
            $response = $msg->process();
            if ($response !== null) {
                $response->write($this->streamOut);
            }
        } while ($response !== null);
    }

    public static function getInstance()
    {
        return self::$serverInstance;
    }
}
