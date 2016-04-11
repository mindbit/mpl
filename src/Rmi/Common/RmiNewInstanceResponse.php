<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiBaseResponse;

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