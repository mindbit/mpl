<?php
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