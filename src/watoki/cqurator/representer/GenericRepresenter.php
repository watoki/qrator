<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\contracts\Representer;
use watoki\cqurator\form\Field;
use watoki\cqurator\form\StringField;
use watoki\factory\Factory;

class GenericRepresenter implements Representer {

    /** @var array|\watoki\cqurator\contracts\Query[] */
    private $queries = [];

    /** @var array|\watoki\cqurator\contracts\Command[] */
    private $commands = [];

    /** @var null|callable */
    private $renderer;

    /** @var Factory */
    private $factory;

    /** @var array|Field[] */
    private $fields = [];

    /**
     * @param Factory $factory <-
     */
    public function __construct(Factory $factory) {
        $this->factory = $factory;
    }


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
     * @return array|\watoki\cqurator\contracts\Query[]
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * @return array|\watoki\cqurator\contracts\Command[]
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
        return new StringField($this->factory, $name);
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
        return get_class($object);
    }

    /**
     * @param object $object
     * @throws \InvalidArgumentException
     * @return array|Property[]
     */
    public function getProperties($object) {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("Not an object: " . var_export($object, true));
        }
        $properties = [];

        foreach ($object as $property => $value) {
            $properties[] = new PublicProperty($object, $property);;
        }
        foreach (get_class_methods(get_class($object)) as $method) {
            if (substr($method, 0, 3) == 'set' || substr($method, 0, 3) == 'get') {
                $properties[] = new AccessorProperty($object, lcfirst(substr($method, 3)));
            }
        }

        return $properties;
    }
}