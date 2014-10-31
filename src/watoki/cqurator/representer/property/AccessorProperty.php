<?php
namespace watoki\cqurator\representer\property;

use watoki\cqurator\representer\property\ObjectProperty;

class AccessorProperty extends ObjectProperty {

    public function get() {
        $method = $this->getMethod();
        $this->guardMethodExists($method);
        return call_user_func(array($this->object, $method));
    }

    public function set($value) {
        $method = $this->setMethod();
        $this->guardMethodExists($method);
        call_user_func(array($this->object, $method), $value);
    }

    private function guardMethodExists($method) {
        if (!method_exists($this->object, $method)) {
            $class = get_class($this->object);
            throw new \Exception("Cannot access value of property [{$this->name()}]. Method [$class::$method] does not exist.");
        }
    }

    public function canGet() {
        return method_exists($this->object, $this->getMethod());
    }

    public function canSet() {
        return method_exists($this->object, $this->setMethod());
    }

    protected function getMethod() {
        return 'get' . ucfirst($this->name());
    }

    protected function setMethod() {
        return 'set' . ucfirst($this->name());
    }
}