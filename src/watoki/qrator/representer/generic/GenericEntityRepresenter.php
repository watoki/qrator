<?php
namespace watoki\qrator\representer\generic;

use watoki\qrator\representer\basic\BasicEntityRepresenter;

class GenericEntityRepresenter extends BasicEntityRepresenter {

    /** @var callable */
    private $actionsGenerator;

    /** @var array|callable[] */
    private $propertyActionGenerators = [];

    /** @var null|callable */
    private $renderer;

    /** @var \watoki\qrator\representer\ActionLink|null */
    private $listAction;

    /** @var callable|null */
    private $readActionGenerator;

    /** @var null|callable */
    private $stringifier;

    /** @var string */
    private $class;

    /** @var string|null */
    private $name;

    /**
     * @param string $class
     */
    public function __construct($class) {
        $this->class = $class;
        $this->actionsGenerator = function () {
            return [];
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
     * @param callable $generator
     * @return $this
     */
    public function setActions($generator) {
        $this->actionsGenerator = $generator;
        return $this;
    }

    /**
     * @param object $entity
     * @return array|\watoki\qrator\representer\ActionLink[]
     */
    public function getActions($entity) {
        return call_user_func($this->actionsGenerator, $entity);
    }

    /**
     * @param string $property
     * @param callable $generator
     * @return $this
     */
    public function setPropertyAction($property, $generator) {
        $this->propertyActionGenerators[$property] = $generator;
        return $this;
    }

    /**
     * @param object $entity
     * @param string $propertyName
     * @return array|\watoki\qrator\representer\ActionLink[]
     */
    public function getPropertyActions($entity, $propertyName, $value) {
        if (!isset($this->propertyActionGenerators[$propertyName])) {
            return [];
        }
        return call_user_func($this->propertyActionGenerators[$propertyName], $entity, $propertyName, $value);
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
     * @param object $entity
     * @return string
     */
    public function render($entity) {
        if ($this->renderer) {
            return call_user_func($this->renderer, $entity);
        }
        return parent::render($entity);
    }

    /**
     * @return null|object
     */
    public function getListAction() {
        return $this->listAction;
    }

    /**
     * @param null|\watoki\qrator\representer\ActionLink $listAction
     * @return $this
     */
    public function setListAction($listAction) {
        $this->listAction = $listAction;
        return $this;
    }

    /**
     * @param object $entity
     * @return null|object
     */
    public function getReadAction($entity) {
        return $this->readActionGenerator
            ? call_user_func($this->readActionGenerator, $entity)
            : null;
    }

    /**
     * @param null|callable $generator
     * @return $this
     */
    public function setReadAction($generator) {
        $this->readActionGenerator = $generator;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getName() {
        return $this->name ?: parent::getName();
    }

    /**
     * @param null|string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
}