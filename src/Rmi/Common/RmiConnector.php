<?php
abstract class RmiConnector {
    protected $streamIn, $streamOut;

    function getStreamIn() {
        return $this->streamIn;
    }

    function getStreamOut() {
        return $this->streamOut;
    }
}