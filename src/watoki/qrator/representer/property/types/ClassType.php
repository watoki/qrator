<?php
namespace watoki\qrator\representer\property\types;

class ClassType {

    /** @var string */
    private $class;

    /**
     * @param string $class
     */
    function __construct($class) {
        $this->class = trim($class, '\\');
    }

    /**
     * @return string
     */
    public function getClass() {
        return $this->class;
    }

} 