<?php
namespace watoki\qrator\representer;

use watoki\collections\Map;
use watoki\qrator\representer\property\AccessorProperty;
use watoki\qrator\representer\property\ClassProperty;
use watoki\qrator\representer\property\ConstructorProperty;
use watoki\qrator\representer\property\ObjectProperty;
use watoki\qrator\representer\property\PublicProperty;
use watoki\qrator\Representer;

abstract class GenericRepresenter implements Representer {

    /** @var null|callable */
    private $stringifier;

    /** @var string */
    private $class;

    /**
     * @param string $class
     */
    public function __construct($class) {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getName() {
        $class = new \ReflectionClass($this->class);
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $class->getShortName());
    }

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object) {
        $properties = $this->getProperties($object);
        if (isset($properties['id']) && $properties['id']->canGet()) {
            return $properties['id']->get();
        } else {
            return null;
        }
    }

    /**
     * @param object|null $object
     * @return string
     */
    public function toString($object = null) {
        if ($this->stringifier) {
            return call_user_func($this->stringifier, $object);
        }

        $propertyString = '';
        $properties = $this->getProperties($object);
        if (!$properties->isEmpty()) {
            $propertyString =
                ' [' .
                $properties
                    ->filter(function (ObjectProperty $property) {
                        return $property->canGet() && $property->get();
                    })
                    ->map(function (ObjectProperty $property) {
                        return $property->name() . ':' . print_r($property->get(), true);
                    })
                    ->asList()
                    ->join('|')
                . ']';
        }
        return $this->getName() . $propertyString;
    }

    /**
     * @param callable $callback
     */
    public function setStringifier($callback) {
        $this->stringifier = $callback;
    }

    /**
     * @param object|null $object
     * @throws \InvalidArgumentException
     * @return \watoki\collections\Map|ObjectProperty[] indexed by property name
     */
    public function getProperties($object = null) {
        if (is_object($object)) {
            return $this->getObjectProperties($object);
        } else {
            return $this->getClassProperties();
        }
    }

    private function getObjectProperties($action) {
        /** @var Map|Property[] $properties */
        $properties = new Map();
        $reflection = new \ReflectionClass($action);

        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $properties[$parameter->getName()] =
                    new ConstructorProperty($action, $parameter->getName(), !$parameter->isDefaultValueAvailable());
            }
        }

        foreach ($action as $property => $value) {
            $isRequired = $properties->has($property) && $properties[$property]->isRequired();
            $properties[$property] = new PublicProperty($action, $property, $isRequired);;
        }

        foreach (get_class_methods(get_class($action)) as $method) {
            if (substr($method, 0, 3) == 'set' || substr($method, 0, 3) == 'get') {
                $name = lcfirst(substr($method, 3));
                if (!$properties->has($name)) {
                    $properties[$name] = new AccessorProperty($action, $name);
                }
            }
        }
        return $properties;
    }

    private function getClassProperties() {
        $properties = new Map();
        $reflection = new \ReflectionClass($this->class);

        $canSet = [];
        $canGet = [];
        $required = [];

        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $required[$parameter->getName()] = !$parameter->isDefaultValueAvailable();
                $properties[$parameter->getName()] =
                    new ClassProperty($parameter->getName(), $required[$parameter->getName()], false, true);
                $canSet[$parameter->getName()] = true;
            }
        }

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $isRequired = isset($required[$property->getName()]) && $required[$property->getName()];
            $properties[$property->getName()] = new ClassProperty($property->getName(), $isRequired, true, true);
            $canGet[$property->getName()] = true;
            $canSet[$property->getName()] = true;
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            $isGetter = substr($name, 0, 3) == 'get';
            $isSetter = substr($name, 0, 3) == 'set';
            if ($isGetter || $isSetter) {
                $name = lcfirst(substr($name, 3));
                $canSet[$name] = isset($canSet[$name]) && $canSet[$name] || $isSetter;
                $canGet[$name] = isset($canGet[$name]) && $canGet[$name] || $isGetter;
                $isRequired = isset($required[$name]) && $required[$name];
                $properties[$name] = new ClassProperty($name, $isRequired, $canGet[$name], $canSet[$name]);
            }
        }
        return $properties;
    }
}