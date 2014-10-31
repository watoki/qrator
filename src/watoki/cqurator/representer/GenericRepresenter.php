<?php
namespace watoki\cqurator\representer;

use watoki\collections\Map;
use watoki\cqurator\Representer;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\StringField;
use watoki\cqurator\representer\property\AccessorProperty;
use watoki\cqurator\representer\property\ConstructorProperty;
use watoki\cqurator\representer\property\PublicProperty;

abstract class GenericRepresenter implements Representer {

    /** @var null|callable */
    private $stringifier;

    /**
     * @param string $class
     * @return string
     */
    public function getName($class) {
        $class = new \ReflectionClass($class);
        return $class->getShortName();
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
     * @param object $object
     * @return string
     */
    public function toString($object) {
        if ($this->stringifier) {
            return call_user_func($this->stringifier, $object);
        }
        $propertyString = '';
        $properties = $this->getProperties($object);
        if (!$properties->isEmpty()) {
            $propertyString =
                '[' .
                $properties
                    ->filter(function (Property $property) {
                        return $property->canGet() && $property->get();
                    })
                    ->map(function (Property $property) {
                        return $property->name . ':' . print_r($property->get(), true);
                    })
                    ->asList()
                    ->join('|')
                . ']';
        }
        return $this->getName(get_class($object)) . $propertyString;
    }

    /**
     * @param callable $callback
     */
    public function setStringifier($callback) {
        $this->stringifier = $callback;
    }

    /**
     * @param object $object
     * @throws \InvalidArgumentException
     * @return \watoki\collections\Map|Property[] indexed with property name
     */
    public function getProperties($object) {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("Not an object: " . var_export($object, true));
        }
        $properties = new Map();

        $class = new \ReflectionClass($object);
        $constructor = $class->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $properties[$parameter->getName()] = new ConstructorProperty($object, $parameter->getName());
            }
        }

        foreach ($object as $property => $value) {
            $properties[$property] = new PublicProperty($object, $property);;
        }

        foreach (get_class_methods(get_class($object)) as $method) {
            if (substr($method, 0, 3) == 'set' || substr($method, 0, 3) == 'get') {
                $name = lcfirst(substr($method, 3));
                if (!$properties->has($name)) {
                    $properties[$name] = new AccessorProperty($object, $name);
                }
            }
        }

        return $properties;
    }
}