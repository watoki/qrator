<?php
namespace watoki\qrator\representer\property;

use watoki\qrator\representer\Property;

class PublicProperty extends Property {

    /** @var \ReflectionProperty */
    private $property;

    public function __construct(\ReflectionProperty $property, $required = false, $type = null) {
        parent::__construct($property->getName(), $required, $type);
        $this->property = $property;
    }

    public function get($object) {
        return $this->property->getValue($object);
    }

    public function set($object, $value) {
        $this->property->setValue($object, $value);
    }

    public function canGet() {
        return true;
    }

    public function canSet() {
        return true;
    }

    public function defaultValue() {
        return $this->property->isDefault()
            ? $this->property->getDeclaringClass()->getDefaultProperties()[$this->property->getName()]
            : null;
    }

    public function type() {
        return $this->findType('/@var\s+(\S+).*/', $this->property->getDocComment(),
            $this->property->getDeclaringClass());
    }
}