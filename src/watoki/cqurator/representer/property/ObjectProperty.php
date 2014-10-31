<?php
namespace watoki\cqurator\representer\property;

abstract class ObjectProperty implements \watoki\cqurator\representer\Property {

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