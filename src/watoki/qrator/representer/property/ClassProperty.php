<?php
namespace watoki\qrator\representer\property;

use watoki\qrator\representer\Property;

class ClassProperty implements Property {

    private $name;

    private $required;

    private $canGet;

    private $canSet;

    function __construct($name, $required = false, $canGet = false, $canSet = false) {
        $this->name = $name;
        $this->required = $required;
        $this->canGet = $canGet;
        $this->canSet = $canSet;
    }

    public function name() {
        return $this->name;
    }

    public function isRequired() {
        return $this->required;
    }

    public function canGet() {
        return $this->canGet;
    }

    public function canSet() {
        return $this->canSet;
    }

    public function get() {
    }

    public function set($value) {
    }
}