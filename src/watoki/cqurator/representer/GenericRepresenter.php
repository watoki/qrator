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
        foreach ($this->getPropertyValues($object) as $name => $value) {
            $field = $this->getField($name, $value);
            $field->setValue($value);
            $fields[] = $field;
        }
        return $fields;
    }

    /**
     * @param $name
     * @param $value
     * @return Field
     */
    protected function getField($name, $value) {
        return new StringField($this->factory, $name);
    }

    /**
     * @param object $object
     * @throws \InvalidArgumentException
     * @return array|\mixed[] Indexed by property names
     */
    public function getPropertyValues($object) {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("Not an object: " . var_export($object, true));
        }
        $properties = [];

        foreach ($object as $name => $value) {
            $properties[$name] = $value;
        }

        $reflection = new \ReflectionClass($object);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && substr($method->getName(), 0, 3) == 'get') {
                $properties[substr($method->getName(), 3)] = $method->invoke($object);
            }
        }

        return $properties;
    }

    /**
     * @param object $object
     * @return string
     */
    public function toString($object) {
        return get_class($object);
    }
}