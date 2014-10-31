<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\EntityRepresenter;

class GenericEntityRepresenter extends GenericRepresenter implements EntityRepresenter {

    /** @var array|string[] */
    private $queries = [];

    /** @var array|string[] */
    private $commands = [];

    /** @var array|array[] Arrays of query classes indexed by property names */
    private $propertyQueries = [];

    /** @var array|array[] Arrays of command classes indexed by property names */
    private $propertyCommands = [];

    /** @var null|callable */
    private $renderer;

    /**
     * @param string $queryClass
     */
    public function addQuery($queryClass) {
        $this->queries[] = $queryClass;
    }

    /**
     * @param string $property
     * @param string $queryClass
     */
    public function addPropertyQuery($property, $queryClass) {
        $this->propertyQueries[$property][] = $queryClass;
    }

    /**
     * @param string $property
     * @return array|string[]
     */
    public function getPropertyQueries($property) {
        if (!isset($this->propertyQueries[$property])) {
            return [];
        }
        return $this->propertyQueries[$property];
    }

    /**
     * @param string $property
     * @param string $commandClass
     */
    public function addPropertyCommand($property, $commandClass) {
        $this->propertyCommands[$property][] = $commandClass;
    }

    /**
     * @param string $property
     * @return \string[]
     */
    public function getPropertyCommands($property) {
        if (!isset($this->propertyCommands[$property])) {
            return [];
        }
        return $this->propertyCommands[$property];
    }

    /**
     * @param string $commandClass
     */
    public function addCommand($commandClass) {
        $this->commands[] = $commandClass;
    }

    /**
     * @return array|string[]
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * @return array|string[]
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