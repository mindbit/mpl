<?php
class RmiStub {
    protected $__rmiId;
    protected $__rmiClient;

    function getRmiId() {
        return $this->__rmiId;
    }

    function setRmiId($rmiId) {
        $this->__rmiId = $rmiId;
    }

    function getRmiClient() {
        return $this->__rmiClient;
    }

    function setRmiClient($rmiClient) {
        $this->__rmiClient = $rmiClient;
    }

    function __call($name, $arguments) {
        return $this->__rmiClient->callMethod($this, $name, $arguments);
    }
}