<?php
namespace Mindbit\Mpl\Asn;

use Mindbit\Mpl\Asn\AsnBase;

class AsnSequence extends AsnBase implements IteratorAggregate, ArrayAccess {
    function parse($string) {
        //echo get_class($this) . " parsing: " ; var_dump($string);
        $this->data = array();
        $endLength = strlen($string);
        $p = 0;
        while ($p < $endLength) {
            // decode object type
            $start = $p;
            $type = ord($string[$p++]);
            if ($type == 0)
                continue;

                // decode object length
                $length = ord($string[$p++]);
                if ($length & Asn::M_LONG_LEN) {
                    $length &= Asn::M_LONG_LEN - 1;
                    $tempLength = 0;
                    if ($p + $length > $endLength)
                        break;
                        while ($length--)
                            $tempLength = ord($string[$p++]) + ($tempLength * 256);
                            $length = $tempLength;
                }

                // decode object contents
                if ($p + $length > $endLength)
                    break;
                    switch ($type & Asn::CLASS_MASK) {
                        case Asn::C_UNIVERSAL:
                            $obj = Asn::newInstance($type);
                            break;
                        case Asn::C_CONTEXT:
                            // FIXME we should check that bit 5 of $type is set
                            $obj = new AsnSequenceIndex($type & Asn::TAG_MASK);
                            break;
                        default:
                            $obj = new AsnUnknown($type);
                    }
                    $obj->parse(substr($string, $p, $length));
                    $this->data[] = $obj;
                    $p = $p + $length;
                    $length = 0;
        }

        if ($length) {
            // we forcedly exited the loop
            $obj = new AsnUnparseable();
            $obj->parse(substr($string, $start, $endLength - $start));
            $this->data[] = $obj;
        }
    }

    function getIterator() {
        return new ArrayIterator($this->data);
    }

    function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }
    function offsetExists($offset) {
        return isset($this->data[$offset]);
    }
    function offsetUnset($offset) {
        unset($this->data[$offset]);
    }
    function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}