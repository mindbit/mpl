<?php

namespace Mindbit\Mpl\Rmi\Server;

use Mindbit\Mpl\Rmi\Common\RmiConnector;

abstract class RmiServer extends RmiConnector {
    protected static $registry = array();
    protected static $serial = 0;
    protected static $serverInstance;

    static function uniqid() {
        return uniqid(sprintf("%04x%04x.", mt_rand() & 0xffff, ++self::$serial & 0xffff), true);
    }

    static function registerObject($object) {
        assert(is_object($object));
        $array = (array)$object;
        if (isset($array["__rmiId"]))
            return $array["__rmiId"];
            $object->__rmiId = self::uniqid();
            self::$registry[$object->__rmiId] = $object;
            return $object->__rmiId;
    }

    static function getObject($rmiId) {
        return isset(self::$registry[$rmiId]) ?
        self::$registry[$rmiId] : null;
    }

    final function run() {
        self::$serverInstance = $this;
        set_time_limit(0);
        ErrorHandler::setHandler(new RmiServerErrorHandler());
        do {
            $msg = RmiMessage::read($this->streamIn);
            if ($msg === null)
                break;
                assert($msg instanceof RmiRequest);
                $response = $msg->process();
                if ($response !== null)
                    $response->write($this->streamOut);
        } while ($response !== null);
    }

    static function getInstance() {
        return self::$serverInstance;
    }
}