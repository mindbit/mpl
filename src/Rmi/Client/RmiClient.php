<?php

namespace Mindbit\Mpl\Rmi\Client;

use Mindbit\Mpl\Rmi\Common\RmiConnector;

abstract class RmiClient extends RmiConnector {
    function createInstance($class) {
        $args = func_get_args();
        array_shift($args);
        $msg = $this->dispatch(new RmiNewInstanceRequest($class, $args));
        assert($msg instanceof RmiNewInstanceResponse);
        $instance = new RmiStub();
        $instance->setRmiId($msg->getRmiId());
        $instance->setRmiClient($this);
        return $instance;
    }

    function callMethod($object, $method, $args) {
        $msg = $this->dispatch(new RmiCallMethodRequest($object->getRmiId(), $method, $args));
        assert($msg instanceof RmiCallMethodResponse);
        return $msg->getRetVal();
    }

    function dispatch($request) {
        $request->write($this->streamOut);
        $response = RmiMessage::read($this->streamIn);
        if ($response === null)
            throw new Exception("Unexpected end of stream");
            assert($response instanceof RmiResponse);
            if ($response instanceof RmiExceptionResponse)
                $this->handleRemoteException($response->getException());
                return $response;
    }

    function handleRemoteException($e) {
        throw $e;
    }
}