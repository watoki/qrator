<?php
namespace watoki\cqurator\representer;

abstract class Property {

    public $name;

    protected $object;

    public function __construct($object, $name) {
        $this->name = $name;
        $this->object = $object;
    }

    abstract public function canGet();

    abstract public function canSet();

    abstract public function get();

    abstract public function set($value);

} 