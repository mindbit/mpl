<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiResponse;

class RmiExceptionResponse extends RmiResponse {
    protected $exception;

    function __construct($exception) {
        $this->exception = $exception;
    }

    function getException() {
        return $this->exception;
    }

    function serialize() {
        return serialize($this);
    }
}