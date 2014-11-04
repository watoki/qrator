<?php
namespace watoki\qrator\representer\property;

use watoki\qrator\representer\Property;
use watoki\qrator\representer\property\types\ClassType;

class ConstructorProperty extends Property {

    /** @var \ReflectionMethod */
    private $constructor;

    /** @var \ReflectionParameter */
    private $parameter;

    public function __construct(\ReflectionMethod $constructor, \ReflectionParameter $parameter, $required = false, $type = null) {
        parent::__construct($parameter->getName(), $required, $type);
        $this->constructor = $constructor;
        $this->parameter = $parameter;
    }

    public function isRequired() {
        return !$this->parameter->isDefaultValueAvailable();
    }

    public function canGet() {
        return false;
    }

    public function canSet() {
        return true;
    }

    public function get($object) {
    }

    public function set($object, $value) {
    }

    public function type() {
        if ($this->parameter->getClass()) {
            return new ClassType($this->parameter->getClass()->getName());
        }
        $pattern = '/@param\s+(\S+)\s+\$' . $this->parameter->getName() . '/';
        return $this->findType($pattern, $this->constructor->getDocComment(), $this->constructor->getDeclaringClass());
    }
}