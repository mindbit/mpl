<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiResponse;

abstract class RmiBaseResponse extends RmiResponse {
    /**
     * Arguments that have been changed from within the
     * called function (constructor or method).
     */
    protected $args;

    function __construct($args) {
        $this->args = $args;
    }

    function serialize() {
        // TODO this must make any server-to-client conversions
        // prior to the real serialization
        return serialize($this);
    }
}