<?php
namespace watoki\qrator\representer\property\types;

class ArrayType {

    /** @var object */
    private $itemType;

    function __construct($itemType) {
        $this->itemType = $itemType;
    }

    /**
     * @return object
     */
    public function getItemType() {
        return $this->itemType;
    }

} 