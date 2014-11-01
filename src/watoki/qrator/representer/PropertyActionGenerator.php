<?php
namespace watoki\qrator\representer;

class PropertyActionGenerator extends ActionGenerator {

    /**
     * @param string $class
     * @param null|callable $argumentGenerator Takes entity ID and property ID and returns array with action arguments
     */
    public function __construct($class, $argumentGenerator = null) {
        return parent::__construct($class, $argumentGenerator ? $argumentGenerator : function ($id, $propertyId) {
            if (!$id || !$propertyId) {
                return [];
            }
            return [
                'id' => $id,
                'object' => $propertyId
            ];
        });
    }

    public function getArguments($id, $propertyId = null) {
        return call_user_func($this->argumentGenerator, $id, $propertyId);
    }


} 