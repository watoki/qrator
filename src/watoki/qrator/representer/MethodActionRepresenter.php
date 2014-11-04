<?php
namespace watoki\qrator\representer;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\factory\providers\CallbackProvider;
use watoki\qrator\representer\generic\GenericActionRepresenter;
use watoki\qrator\representer\property\PublicProperty;

class MethodActionRepresenter extends GenericActionRepresenter{

    /** @var \ReflectionMethod */
    private $method;

    /**
     * @param string $className
     * @param string $methodName
     * @param Factory $factory <-
     */
    public function __construct($className, $methodName, Factory $factory) {
        parent::__construct(self::asClass($className, $methodName), $factory);

        $this->method = new \ReflectionMethod($className, $methodName);

        $fullClassName = $this->getClass();

        if (!class_exists($fullClassName)) {
            $this->createClassDefinition($fullClassName);
        }

        $factory->setProvider($this->getClass(), new CallbackProvider(function () use ($fullClassName) {
            return new $fullClassName;
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

    public static function asClass($class, $method) {
        return $class . '__' . $method;
    }

    /**
     * @return string
     */
    public function getName() {
        return ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->method->getShortName()));
    }

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\qrator\representer\property\ObjectProperty[]  indexed by property name
     */
    public function getProperties($object = null) {
        $properties = new Map();
        foreach ($this->method->getParameters() as $parameter) {
            $name = $parameter->getName();
            if ($object && !isset($object->$name)) {
                $object->$name = null;
            }
            $properties->set($parameter->getName(),
                new PublicProperty($object, $parameter->getName(), !$parameter->isDefaultValueAvailable()));
        }
        return $properties;
    }

    private function createClassDefinition($fullClassName) {
        $parts = explode('\\', $fullClassName);
        $shortName = array_pop($parts);
        $namespace = implode('\\', $parts);

        eval(($namespace ? "namespace $namespace; " : '') . "class $shortName {}");
    }
}