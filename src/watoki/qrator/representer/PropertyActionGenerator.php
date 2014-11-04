<?php
namespace watoki\qrator\representer;

class PropertyActionGenerator extends ActionGenerator {

    /**
     * @param string $class
     * @param null|callable $arguments Takes entity ID and property ID and returns array with action arguments
     */
    public function __construct($class, $arguments = null) {
        return parent::__construct($class, $arguments ? $arguments : function ($id, $propertyId) {
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