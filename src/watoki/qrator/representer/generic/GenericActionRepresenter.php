<?php
namespace watoki\qrator\representer\generic;

use watoki\factory\Factory;
use watoki\qrator\form\Field;
use watoki\qrator\representer\ActionGenerator;
use watoki\qrator\representer\basic\BasicActionRepresenter;

class GenericActionRepresenter extends BasicActionRepresenter {

    /** @var array|Field[] */
    private $fields = [];

    /** @var null|ActionGenerator */
    private $followUpAction;

    /** @var callable */
    private $handler;

    /** @var callable */
    private $preFiller;

    /** @var null|callable */
    private $stringifier;

    /** @var string */
    private $class;

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
    public function getClass() {
        return $this->class;
    }

    /**
     * @param object|null $object
     * @return string
     */
    public function toString($object = null) {
        if ($this->stringifier) {
            return call_user_func($this->stringifier, $object);
        }

        return parent::toString($object);
    }

    /**
     * @param callable $callback
     */
    public function setStringifier($callback) {
        $this->stringifier = $callback;
    }

    /**
     * @param callable|object|string $handler
     */
    public function setHandler($handler) {
        $this->handler = $this->makeCallable($handler);
    }

    /**
     * @param object $object of the action to be executed
     * @return mixed
     */
    public function execute($object) {
        return call_user_func($this->handler, $object);
    }

    /**
     * @param $name
     * @return Field
     */
    public function getField($name) {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }
        return parent::getField($name);
    }

    /**
     * @param string $name
     * @param Field $field
     */
    public function setField($name, Field $field) {
        $this->fields[$name] = $field;
    }

    public function setFollowUpAction(ActionGenerator $action) {
        $this->followUpAction = $action;
    }

    /**
     * @return null|ActionGenerator
     */
    public function getFollowUpAction() {
        return $this->followUpAction;
    }

    public function preFill($object) {
        call_user_func($this->preFiller, $object);
    }

    /**
     * @param callable $preFiller
     */
    public function setPreFiller($preFiller) {
        $this->preFiller = $preFiller;
    }
}