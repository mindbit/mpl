<?php
abstract class RmiRequest extends RmiMessage {
    abstract function process();
}