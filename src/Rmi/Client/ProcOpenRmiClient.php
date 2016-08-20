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

use Mindbit\Mpl\Rmi\Client\RmiClient;

class ProcOpenRmiClient extends RmiClient
{
    protected $process;
    protected $streamErr;

    public function __construct($cmd, $stderr = array("file", "/dev/null", "a"))
    {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => $stderr);
        $this->process = proc_open($cmd, $descriptorspec, $pipes);
        assert(is_resource($this->process));
        $this->streamIn = $pipes[1];
        $this->streamOut = $pipes[0];
        if (isset($pipes[2])) {
            $this->streamErr = $pipes[2];
        }
    }
}
