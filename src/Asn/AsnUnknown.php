<?php
namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase; 

class AsnUnknown extends AsnBase {
    protected $type;

    function __construct($type) {
        $this->type = $type;
    }

    function getType() {
        return $this->type;
    }
}