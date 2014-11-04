<?php
namespace watoki\qrator\representer\property;

use watoki\qrator\representer\Property;
use watoki\qrator\representer\property\types\ClassType;

class AccessorProperty extends Property {

    /** @var \ReflectionMethod|null */
    private $getter;

    /** @var \ReflectionMethod|null */
    private $setter;

    public function __construct(\ReflectionMethod $method, $required = false, $type = null) {
        parent::__construct(lcfirst(substr($method->getName(), 3)), $required, $type);

        if (substr($method->getName(), 0, 3) == 'get') {
            $this->getter = $method;
        } else {
            $this->setter = $method;
        }
    }

    public function get($object) {
        return $this->getter->invoke($object);
    }

    public function set($object, $value) {
        $this->setter->invoke($object, $value);
    }

    public function canGet() {
        return !!$this->getter;
    }

    public function canSet() {
        return !!$this->setter;
    }

    public function type() {
        if ($this->getter) {
            return $this->findType('/@return\s+(\S+)/', $this->getter->getDocComment(),
                $this->getter->getDeclaringClass());
        } else if ($this->setter) {
            $param = $this->setter->getParameters()[0];
            if ($param->getClass()) {
                return new ClassType($param->getClass()->getName());
            }
            return $this->findType('/@param\s+(\S+)/', $this->setter->getDocComment(),
                $this->setter->getDeclaringClass());
        }
        return null;
    }
}