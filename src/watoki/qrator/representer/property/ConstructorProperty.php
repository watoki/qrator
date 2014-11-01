<?php
namespace watoki\qrator\representer\property;

class ConstructorProperty extends AccessorProperty {

    public function canSet() {
        return true;
    }

    public function set($value) {
        $method = $this->setMethod();
        if (method_exists($this->object, $method)) {
            call_user_func(array($this->object, $method), $value);
        }
    }
}