<?php
namespace watoki\qrator\representer;

use watoki\qrator\EntityRepresenter;

class GenericEntityRepresenter extends GenericRepresenter implements EntityRepresenter {

    /** @var array|ActionGenerator[] */
    private $queries = [];

    /** @var array|ActionGenerator[] */
    private $commands = [];

    /** @var array|array[] Arrays of PropertyActionGenerator indexed by property names */
    private $propertyQueries = [];

    /** @var array|array[] Arrays of PropertyActionGenerator indexed by property names */
    private $propertyCommands = [];

    /** @var null|callable */
    private $renderer;

    /**
     * @param ActionGenerator $queryClass
     */
    public function addQuery(ActionGenerator $queryClass) {
        $this->queries[] = $queryClass;
    }

    /**
     * @param string $property
     * @param PropertyActionGenerator $query
     */
    public function addPropertyQuery($property, PropertyActionGenerator $query) {
        $this->propertyQueries[$property][] = $query;
    }

    /**
     * @param string $property
     * @return array|PropertyActionGenerator[]
     */
    public function getPropertyQueries($property) {
        if (!isset($this->propertyQueries[$property])) {
            return [];
        }
        return $this->propertyQueries[$property];
    }

    /**
     * @param string $property
     * @param PropertyActionGenerator $command
     */
    public function addPropertyCommand($property, PropertyActionGenerator $command) {
        $this->propertyCommands[$property][] = $command;
    }

    /**
     * @param string $property
     * @return PropertyActionGenerator[]
     */
    public function getPropertyCommands($property) {
        if (!isset($this->propertyCommands[$property])) {
            return [];
        }
        return $this->propertyCommands[$property];
    }

    /**
     * @param ActionGenerator $commandClass
     */
    public function addCommand(ActionGenerator $commandClass) {
        $this->commands[] = $commandClass;
    }

    /**
     * @return array|ActionGenerator[]
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * @return array|ActionGenerator[]
     */
    public function getCommands() {
        return $this->commands;
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
}