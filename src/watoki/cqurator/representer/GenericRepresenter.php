<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\Representer;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\StringField;
use watoki\cqurator\representer\property\AccessorProperty;
use watoki\cqurator\representer\property\PublicProperty;

class GenericRepresenter implements Representer {

    /** @var array|string[] */
    private $queries = [];

    /** @var array|string[] */
    private $commands = [];

    /** @var null|callable */
    private $renderer;

    /** @var array|Field[] */
    private $fields = [];

    /**
     * @param string $queryClass
     */
    public function addQuery($queryClass) {
        $this->queries[] = $queryClass;
    }

    /**
     * @param string $commandClass
     */
    public function addCommand($commandClass) {
        $this->commands[] = $commandClass;
    }

    /**
     * @return array|string[]
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * @return array|string[]
     */
    public function getCommands() {
        return $this->commands;
    }

    public function setRenderer($renderer) {
        $this->renderer = $renderer;
    }

    /**
     * @param object $value
     * @return string
     */
    public function render($value) {
        if ($this->renderer) {
            return call_user_func($this->renderer, $value);
        }
        return print_r($value, true);
    }

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object) {
        if (isset($object->id)) {
            return $object->id;
        } else if (method_exists($object, 'getId')) {
            return $object->getId();
        } else {
            return null;
        }
    }

    /**
     * @param object $object
     * @return array|\watoki\cqurator\form\Field[]
     */
    public function getFields($object) {
        $fields = [];
        foreach ($this->getProperties($object) as $property) {
            if (!$property->canSet()) {
                continue;
            }

            $field = $this->getField($property->name);
            $fields[] = $field;

            if ($property->canGet()) {
                $field->setValue($property->get());
            }
        }
        return $fields;
    }

    /**
     * @param $name
     * @return Field
     */
    public function getField($name) {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }
        return new StringField($name);
    }

    /**
     * @param string $name
     * @param Field $field
     */
    public function setField($name, Field $field) {
        $this->fields[$name] = $field;
    }

    /**
     * @param object $object
     * @return string
     */
    public function toString($object) {
        $class = new \ReflectionClass($object);
        return $class->getShortName();
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