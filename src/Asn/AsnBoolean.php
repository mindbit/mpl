<?php
namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase;

class AsnBoolean extends AsnBase {
    function parse($string) {
        $this->data = (bool)$string;
    }
}