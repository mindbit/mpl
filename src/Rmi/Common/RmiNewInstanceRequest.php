<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiBaseRequest;

class RmiNewInstanceRequest extends RmiBaseRequest {
    protected $class;

    function __construct($class, $args) {
        $this->class = $class;
        $this->args = $args;
    }

    function process() {
        $refClass = new ReflectionClass($this->class);
        try {
            $instance = $refClass->getConstructor() === null ?
            $refClass->newInstance() :
            $refClass->newInstanceArgs($this->args);
        } catch (Exception $e) {
            return new RmiExceptionResponse($e);
        }
        return new RmiNewInstanceResponse(RmiServer::registerObject($instance), null);
    }
}