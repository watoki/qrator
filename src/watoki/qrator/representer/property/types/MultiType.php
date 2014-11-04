<?php
namespace watoki\qrator\representer\property\types;

class MultiType {

    /** @var object[] */
    private $types;

    function __construct($type) {
        $this->types = $type;
    }

    /**
     * @return \object[]
     */
    public function getTypes() {
        return $this->types;
    }

}