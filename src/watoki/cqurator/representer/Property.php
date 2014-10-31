<?php
namespace watoki\cqurator\representer;

abstract class Property {

    public $name;

    protected $object;

    protected $required;

    public function __construct($object, $name, $required = false) {
        $this->name = $name;
        $this->object = $object;
        $this->required = $required;
    }

    public function isRequired() {
        return $this->required;
    }

    abstract public function canGet();

    abstract public function canSet();

    abstract public function get();

    abstract public function set($value);

} 