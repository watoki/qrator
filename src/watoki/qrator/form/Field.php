<?php
namespace watoki\qrator\form;

abstract class Field {

    /** @var string */
    private $name;

    /** @var mixed */
    private $value;

    /** @var string */
    private $label;

    /** @var bool */
    private $required = false;

    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
        $this->label = ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->getName()));
    }

    /**
     * @return string
     */
    abstract public function render();

    /**
     * @param string $value
     * @return mixed
     */
    public function inflate($value) {
        return $value;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value) {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param bool $to
     * @return void
     */
    public function setRequired($to = true) {
        $this->required = $to;
    }

    /**
     * @return bool
     */
    public function isRequired() {
        return $this->required;
    }

    /**
     * @return array Will be added to the head on the PrepareResource page
     */
    public function addToHead() {
        return [];
    }

    /**
     * @return array Will be added to the foot on the PrepareResource page
     */
    public function addToFoot() {
        return [];
    }

    /**
     * @param string $label
     */
    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }

}