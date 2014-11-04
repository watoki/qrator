<?php
namespace watoki\qrator\representer;

use watoki\qrator\EntityRepresenter;

class GenericEntityRepresenter extends GenericRepresenter implements EntityRepresenter {

    /** @var array|ActionGenerator[] */
    private $actions = [];

    /** @var array|array[] Arrays of PropertyActionGenerator indexed by property names */
    private $propertyActions = [];

    /** @var null|callable */
    private $renderer;

    /** @var object|null */
    private $listAction;

    /** @var object|null */
    private $readAction;

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
        return $this->toString($object);
    }

    /**
     * @return null|object
     */
    public function getListAction() {
        return $this->listAction;
    }

    /**
     * @param null|object $listAction
     */
    public function setListAction($listAction) {
        $this->listAction = $listAction;
    }

    /**
     * @return null|object
     */
    public function getReadAction() {
        return $this->readAction;
    }

    /**
     * @param null|object $readAction
     */
    public function setReadAction($readAction) {
        $this->readAction = $readAction;
    }
}