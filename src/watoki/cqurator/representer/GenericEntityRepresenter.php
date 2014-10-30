<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\EntityRepresenter;

class GenericEntityRepresenter extends GenericRepresenter implements EntityRepresenter {

    /** @var array|string[] */
    private $queries = [];

    /** @var array|string[] */
    private $commands = [];

    /** @var null|callable */
    private $renderer;

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
     * @param object $value
     * @return string
     */
    public function render($value) {
        if ($this->renderer) {
            return call_user_func($this->renderer, $value);
        }
        return print_r($value, true);
    }

} 