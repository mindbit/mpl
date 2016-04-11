<?php
namespace Mindbit\Mpl\Rmi\Server;

use Mindbit\Mpl\Error\ThrowErrorHandler;

class RmiServerErrorHandler extends ThrowErrorHandler {
    protected function __handleException($exception) {
        // For unknown reasons, we have an uncaught exception (it
        // probably happened outside the real-object call code).
        // As a last resort, try to respond with an
        // RmiFatalExceptionResponse, since we are going to die
        // anyway.
        $msg = new RmiFatalExceptionResponse($exception);
        $msg->write(RmiServer::getInstance()->getStreamOut());
    }
}