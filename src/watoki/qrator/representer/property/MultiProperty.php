<?php
namespace watoki\qrator\representer\property;

use watoki\qrator\representer\Property;
use watoki\qrator\representer\property\types\MultiType;

class MultiProperty extends Property {

    /** @var array|Property[] */
    private $properties = [];

    public function isRequired() {
        foreach ($this->properties as $property) {
            if ($property->isRequired()) {
                return true;
            }
        }
        return false;
    }

    public function canGet() {
        foreach ($this->properties as $property) {
            if ($property->canGet()) {
                return true;
            }
        }
        return false;
    }

    public function canSet() {
        foreach ($this->properties as $property) {
            if ($property->canSet()) {
                return true;
            }
        }
        return false;
    }

    public function get($object) {
        foreach ($this->properties as $property) {
            if ($property->canGet()) {
                return $property->get($object);
            }
        }
        return null;
    }

    public function set($object, $value) {
        foreach ($this->properties as $property) {
            if ($property->canSet()) {
                $property->set($object, $value);
            }
        }
        return null;
    }

    public function add(Property $property) {
        $this->properties[] = $property;
    }

    public function type() {
        $types = [];
        foreach ($this->properties as $property) {
            $type = $property->type();
            if ($type) {
                $types[] = $type;
            }
        }
        if (!$types) {
            return null;
        } else if (count($types) == 1) {
            return $types[0];
        } else {
            return new MultiType($types);
        }
    }
}