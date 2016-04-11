<?php
namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase;

class AsnInteger extends AsnBase {
    function getBase64() {
        return base64_encode($this->data);
    }
}