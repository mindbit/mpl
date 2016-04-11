<?php

namespace Mindbit\Mpl\Asn;

abstract class AsnBase {
    protected $data;

    function getData() {
        return $this->data;
    }

    function parse($string) {
        $this->data = $string;
    }
}