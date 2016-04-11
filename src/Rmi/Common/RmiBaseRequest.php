<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiRequest;

abstract class RmiBaseRequest extends RmiRequest {
    protected $args;

    function __construct($args) {
        $this->args = $args;
    }

    function serialize() {
        // TODO this must make any client-to-server conversions
        // prior to the real serialization
        return serialize($this);
    }
}