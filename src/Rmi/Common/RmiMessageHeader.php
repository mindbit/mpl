<?php

namespace Mindbit\Mpl\Rmi\Common;

class RmiMessageHeader {
    protected $version = 1;
    protected $dataLength;

    function __construct($dataLen) {
        $this->dataLength = $dataLen;
    }

    function getDataLength() {
        return $this->dataLength;
    }
}