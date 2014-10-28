<?php
namespace watoki\cqurator\representer;

use watoki\cqurator\contracts\Representer;

class GenericRepresenter implements Representer {

    /** @var array|\watoki\cqurator\contracts\Query[] */
    private $queries = [];

    /** @var array|\watoki\cqurator\contracts\Command[] */
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
     * @return array|\watoki\cqurator\contracts\Query[]
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * @return array|\watoki\cqurator\contracts\Command[]
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