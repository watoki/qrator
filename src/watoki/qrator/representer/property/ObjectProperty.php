<?php
namespace watoki\qrator\representer\property;

use watoki\qrator\representer\Property;

abstract class ObjectProperty implements Property {

    private $name;

    protected $object;

    private $required;

    public function __construct($object, $name, $required = false) {
        $this->name = $name;
        $this->object = $object;
        $this->required = $required;
    }

    public function name() {
        return $this->name;
    }

    public function isRequired() {
        return $this->required;
    }

} 