<?php
namespace watoki\cqurator\representer\property;

use watoki\cqurator\representer\Property;

class PublicProperty extends Property {

    public function get() {
        $name = $this->name;
        return $this->object->$name;
    }

    public function set($value) {
        $name = $this->name;
        $this->object->$name = $value;
    }

    public function canGet() {
        return true;
    }

    public function canSet() {
        return true;
    }
}