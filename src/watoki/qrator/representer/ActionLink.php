<?php
namespace watoki\qrator\representer;

use watoki\collections\Map;

class ActionLink {

    /** @var string */
    private $class;

    /** @var Map */
    private $arguments;

    /**
     * @param $class
     * @param null|array|Map $arguments
     */
    public function __construct($class, $arguments = null) {
        $this->arguments = is_array($arguments) ? new Map($arguments) : ($arguments ?: new Map());
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * @return Map
     */
    public function getArguments() {
        return $this->arguments;
    }

} 