<?php
namespace watoki\qrator\representer\property;

class PublicProperty extends ObjectProperty {

    public function get() {
        $name = $this->name();
        return $this->object->$name;
    }

    public function set($value) {
        $name = $this->name();
        $this->object->$name = $value;
    }

    public function canGet() {
        return true;
    }

    public function canSet() {
        return true;
    }
}