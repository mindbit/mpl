<?php
namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnSequence;

class AsnSequenceIndex extends AsnSequence {
    protected $index;

    function __construct($index) {
        $this->index = $index;
    }
}