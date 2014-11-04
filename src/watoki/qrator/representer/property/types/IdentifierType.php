<?php
namespace watoki\qrator\representer\property\types;

class IdentifierType {

    /** @var string */
    private $target;

    function __construct($target) {
        $this->target = trim($target, '\\');
    }

    /**
     * @return string
     */
    public function getTarget() {
        return $this->target;
    }

} 