<?php
namespace watoki\cqurator\representer\property;

use watoki\cqurator\representer\property\ObjectProperty;

class CallbackObjectProperty extends ObjectProperty {

    /** @var null|callable */
    private $getCallback;

    /** @var null */
    private $setCallback;

    public function __construct($object, $name, $getCallback = null, $setCallback = null) {
        parent::__construct($object, $name);
        $this->getCallback = $getCallback;
        $this->setCallback = $setCallback;
    }

    public function canGet() {
        return !!$this->getCallback;
    }

    public function canSet() {
        return !!$this->setCallback;
    }

    public function get() {
        return call_user_func($this->getCallback);
    }

    public function set($value) {
        call_user_func($this->setCallback, $value);
    }
}