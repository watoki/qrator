<?php
namespace watoki\qrator\representer;



use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\factory\providers\CallbackProvider;
use watoki\qrator\representer\property\PublicProperty;

class MethodActionRepresenter extends \watoki\qrator\representer\GenericActionRepresenter{

    /** @var \ReflectionMethod */
    private $method;

    /** @var Factory */
    private $factory;

    public function __construct($className, $methodName, Factory $factory) {
        parent::__construct($className, $factory);
        $this->factory = $factory;
        $this->method = new \ReflectionMethod($className, $methodName);

        $factory->setProvider($this->getClass(), new CallbackProvider(function () {
            return new \StdClass();
        }));
    }

    public function execute($object) {
        $handler = $this->factory->getInstance($this->method->getDeclaringClass()->getName());
        $properties = $this->getProperties($object);

        $args = [];
        foreach ($this->method->getParameters() as $parameter) {
            $args[] = $properties[$parameter->getName()]->get();
        }
        return $this->method->invokeArgs($handler, $args);
    }

    public function getClass() {
        return parent::getClass() . '__' . $this->method->getName();
    }

    /**
     * @return string
     */
    public function getName() {
        return ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->method->getShortName()));
    }

    /**
     * @param object|null $object Object or class reference
     * @return \watoki\collections\Map|\watoki\qrator\representer\property\ObjectProperty[]  indexed by property name
     */
    public function getProperties($object = null) {
        $properties = new Map();
        foreach ($this->method->getParameters() as $parameter) {
            $property = new PublicProperty($object, $parameter->getName(), !$parameter->isDefaultValueAvailable());
            $properties->set($parameter->getName(), $property);
        }
        return $properties;
    }
}