<?php
class StdInOutRmiServer extends RmiServer {
    function __construct() {
        $this->streamIn = fopen("php://stdin", "r");
        $this->streamOut = fopen("php://stdout", "w");
    }
}