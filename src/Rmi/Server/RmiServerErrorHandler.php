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

namespace Mindbit\Mpl\Rmi\Server;

use Mindbit\Mpl\Error\ThrowErrorHandler;

class RmiServerErrorHandler extends ThrowErrorHandler
{
    protected function __handleException($exception)
    {
        // For unknown reasons, we have an uncaught exception (it
        // probably happened outside the real-object call code).
        // As a last resort, try to respond with an
        // RmiFatalExceptionResponse, since we are going to die
        // anyway.
        $msg = new RmiFatalExceptionResponse($exception);
        $msg->write(RmiServer::getInstance()->getStreamOut());
    }
}
