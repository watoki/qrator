<?php
namespace watoki\qrator\representer\property\types;

class NullableType {

    /** @var string */
    private $type;

    function __construct($type) {
        $this->type = $type;
    }

    public function getType() {
        return $this->type;
    }

} 