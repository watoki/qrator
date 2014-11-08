<?php
namespace watoki\qrator\representer\generic;

use watoki\factory\Factory;
use watoki\qrator\form\Field;
use watoki\qrator\representer\basic\BasicActionRepresenter;
use watoki\qrator\representer\Property;

class GenericActionRepresenter extends BasicActionRepresenter {

    /** @var array|Field[] */
    private $fields = [];

    /** @var null|callable */
    private $followUpActionGenerator;

    /** @var callable */
    private $handler;

    /** @var callable */
    private $preFiller;

    /** @var null|callable */
    private $stringifier;

    /** @var string */
    private $class;

    /** @var string */
    private $name;

    /** @var string|null */
    private $confirmation;

    /**
     * @param string $class
     * @param Factory $factory <-
     */
    public function __construct($class, Factory $factory) {
        parent::__construct($factory);

        $this->class = $class;
        $this->handler = function () use ($class) {
            throw new \LogicException("No handler set for [$class]");
        };
        $this->preFiller = function ($action) {
        };
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name ?: parent::getName();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * @param object $object
     * @return string
     */
    public function toString($object) {
        if ($this->stringifier) {
            return call_user_func($this->stringifier, $object);
        }

        return parent::toString($object);
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function setStringifier($callback) {
        $this->stringifier = $callback;
        return $this;
    }

    /**
     * @param callable|object|string $handler
     * @return $this
     */
    public function setHandler($handler) {
        $this->handler = $this->makeCallable($handler);
        return $this;
    }

    /**
     * @param object $object of the action to be executed
     * @return mixed
     */
    public function execute($object) {
        return $this->executeHandler($this->handler, $object);
    }

    /**
     * @param \watoki\qrator\representer\Property $property
     * @return \watoki\qrator\form\Field
     */
    public function getField(Property $property) {
        if (isset($this->fields[$property->name()])) {
            return $this->fields[$property->name()];
        }
        return parent::getField($property);
    }

    /**
     * @param string $name
     * @param Field $field
     * @return $this
     */
    public function setField($name, Field $field) {
        $this->fields[$name] = $field;
        return $this;
    }

    /**
     * @param callable $generator
     * @return $this
     */
    public function setFollowUpAction($generator) {
        $this->followUpActionGenerator = $generator;
        return $this;
    }

    /**
     * @param object $result
     * @return null|\watoki\qrator\representer\ActionLink
     */
    public function getFollowUpAction($result) {
        return $this->followUpActionGenerator
            ? call_user_func($this->followUpActionGenerator, $result)
            : null;
    }

    /**
     * @param array|\watoki\qrator\form\Field[] $fields
     * @return void
     */
    public function preFill($fields) {
        call_user_func($this->preFiller, $fields);
    }

    /**
     * @param callable $preFiller
     * @return $this
     */
    public function setPreFiller($preFiller) {
        $this->preFiller = $preFiller;
        return $this;
    }

    /**
     * @return string|null
     */
    public function requiresConfirmation() {
        return $this->confirmation;
    }

    /**
     * @param null|string|bool $message If true, default message is used
     * @return $this
     */
    public function setRequireConfirmation($message = true) {
        if ($message === true) {
            $message = 'Execute ' . $this->getName() . '?';
        }
        $this->confirmation = $message;
        return $this;
    }
}