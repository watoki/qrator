<?php
namespace watoki\qrator\representer\generic;

use watoki\qrator\representer\ActionGenerator;
use watoki\qrator\representer\basic\BasicEntityRepresenter;
use watoki\qrator\representer\PropertyActionGenerator;

class GenericEntityRepresenter extends BasicEntityRepresenter {

    /** @var array|\watoki\qrator\representer\ActionGenerator[] */
    private $actions = [];

    /** @var array|array[] Arrays of PropertyActionGenerator indexed by property names */
    private $propertyActions = [];

    /** @var null|callable */
    private $renderer;

    /** @var ActionGenerator|null */
    private $listAction;

    /** @var ActionGenerator|null */
    private $readAction;

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
     * @param ActionGenerator $action
     */
    public function addAction(ActionGenerator $action) {
        $this->actions[] = $action;
    }

    /**
     * @return array|ActionGenerator[]
     */
    public function getActions() {
        return $this->actions;
    }

    /**
     * @param string $property
     * @param PropertyActionGenerator $action
     */
    public function addPropertyAction($property, PropertyActionGenerator $action) {
        $this->propertyActions[$property][] = $action;
    }

    /**
     * @param string $property
     * @return array|PropertyActionGenerator[]
     */
    public function getPropertyActions($property) {
        if (!isset($this->propertyActions[$property])) {
            return [];
        }
        return $this->propertyActions[$property];
    }

    public function setRenderer($renderer) {
        $this->renderer = $renderer;
    }

    /**
     * @param object $object
     * @return string
     */
    public function render($object) {
        if ($this->renderer) {
            return call_user_func($this->renderer, $object);
        }
        return parent::render($object);
    }

    /**
     * @return null|ActionGenerator
     */
    public function getListAction() {
        return $this->listAction;
    }

    /**
     * @param null|ActionGenerator $listAction
     */
    public function setListAction(ActionGenerator $listAction) {
        $this->listAction = $listAction;
    }

    /**
     * @return null|ActionGenerator
     */
    public function getReadAction() {
        return $this->readAction;
    }

    /**
     * @param null|ActionGenerator $readAction
     */
    public function setReadAction(ActionGenerator $readAction) {
        $this->readAction = $readAction;
    }
}