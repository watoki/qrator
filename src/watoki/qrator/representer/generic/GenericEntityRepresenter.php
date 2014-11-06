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
     * @param ActionGenerator $action
     * @return $this
     */
    public function addAction(ActionGenerator $action) {
        $this->actions[] = $action;
        return $this;
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
     * @return $this
     */
    public function addPropertyAction($property, PropertyActionGenerator $action) {
        $this->propertyActions[$property][] = $action;
        return $this;
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

    /**
     * @param callable $renderer
     * @return $this
     */
    public function setRenderer($renderer) {
        $this->renderer = $renderer;
        return $this;
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
     * @return $this
     */
    public function setListAction(ActionGenerator $listAction) {
        $this->listAction = $listAction;
        return $this;
    }

    /**
     * @return null|ActionGenerator
     */
    public function getReadAction() {
        return $this->readAction;
    }

    /**
     * @param null|ActionGenerator $readAction
     * @return $this
     */
    public function setReadAction(ActionGenerator $readAction) {
        $this->readAction = $readAction;
        return $this;
    }
}