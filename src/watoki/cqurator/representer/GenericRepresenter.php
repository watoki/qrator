<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\Representer;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\StringField;
use watoki\cqurator\representer\property\AccessorProperty;
use watoki\cqurator\representer\property\PublicProperty;

abstract class GenericRepresenter implements Representer {

    /** @var null|callable */
    private $stringifier;

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
        $class = new \ReflectionClass($object);
        return $class->getShortName();
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
     * @return array|Property[] indexed with property name
     */
    public function getProperties($object) {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("Not an object: " . var_export($object, true));
        }
        $properties = [];

        foreach ($object as $property => $value) {
            $properties[$property] = new PublicProperty($object, $property);;
        }
        foreach (get_class_methods(get_class($object)) as $method) {
            if (substr($method, 0, 3) == 'set' || substr($method, 0, 3) == 'get') {
                $name = lcfirst(substr($method, 3));
                if (!array_key_exists($name, $properties)) {
                    $properties[$name] = new AccessorProperty($object, $name);
                }
            }
        }

        return $properties;
    }
}