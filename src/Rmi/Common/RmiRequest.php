<?php

namespace Mindbit\Mpl\Rmi\Common;

use Mindbit\Mpl\Rmi\Common\RmiMessage;

abstract class RmiRequest extends RmiMessage {
    abstract function process();
}