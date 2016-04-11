<?php

namespace Mindbit\Mpl\Rmi\Client;

use Mindbit\Mpl\Rmi\Client\RmiClient;

class ProcOpenRmiClient extends RmiClient {
    protected $process;
    protected $streamErr;

    function __construct($cmd, $stderr = array("file", "/dev/null", "a")) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => $stderr);
        $this->process = proc_open($cmd, $descriptorspec, $pipes);
        assert(is_resource($this->process));
        $this->streamIn = $pipes[1];
        $this->streamOut = $pipes[0];
        if (isset($pipes[2]))
            $this->streamErr = $pipes[2];
    }
}