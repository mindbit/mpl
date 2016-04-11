<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiBaseResponse;

class RmiCallMethodResponse extends RmiBaseResponse {
    protected $retVal;

    function __construct($retVal, $args) {
        $this->retVal = $retVal;
        $this->args = $args;
    }

    function getRetVal() {
        return $this->retVal;
    }
}