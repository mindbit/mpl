<?php
/*    Mindbit PHP Library
 *    Copyright (C) 2009 Mindbit SRL
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 2.1 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\Rmi\Client;

use Mindbit\Mpl\Rmi\Common\RmiConnector;

abstract class RmiClient extends RmiConnector
{
    public function createInstance($class)
    {
        $args = func_get_args();
        array_shift($args);
        $msg = $this->dispatch(new RmiNewInstanceRequest($class, $args));
        assert($msg instanceof RmiNewInstanceResponse);
        $instance = new RmiStub();
        $instance->setRmiId($msg->getRmiId());
        $instance->setRmiClient($this);
        return $instance;
    }

    public function callMethod($object, $method, $args)
    {
        $msg = $this->dispatch(new RmiCallMethodRequest($object->getRmiId(), $method, $args));
        assert($msg instanceof RmiCallMethodResponse);
        return $msg->getRetVal();
    }

    public function dispatch($request)
    {
        $request->write($this->streamOut);
        $response = RmiMessage::read($this->streamIn);
        if ($response === null) {
            throw new Exception("Unexpected end of stream");
        }
        assert($response instanceof RmiResponse);
        if ($response instanceof RmiExceptionResponse) {
            $this->handleRemoteException($response->getException());
        }
        return $response;
    }

    public function handleRemoteException($e)
    {
        throw $e;
    }
}
