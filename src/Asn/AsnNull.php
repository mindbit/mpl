<?php
namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase;

class AsnNull extends AsnBase {
    function parse($string) {
        $this->data = null;
    }
}