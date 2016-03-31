<?php
class RmiNewInstanceResponse extends RmiBaseResponse {
    protected $rmiId;

    function __construct($rmiId, $args) {
        $this->rmiId = $rmiId;
        $this->args = $args;
    }

    function getRmiId() {
        return $this->rmiId;
    }
}