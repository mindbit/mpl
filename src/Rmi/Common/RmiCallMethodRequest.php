<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiBaseRequest;

class RmiCallMethodRequest extends RmiBaseRequest {
    protected $rmiId;
    protected $method;

    function __construct($rmiId, $method, $args) {
        $this->rmiId = $rmiId;
        $this->method = $method;
        $this->args = $args;
    }

    function process() {
        $instance = RmiServer::getObject($this->rmiId);
        $refObject = new ReflectionObject($instance);
        try {
            $ret = call_user_func_array(array($instance, $this->method), $this->args);
        } catch (Exception $e) {
            return new RmiExceptionResponse($e);
        }
        return new RmiCallMethodResponse($ret, null);
    }
}