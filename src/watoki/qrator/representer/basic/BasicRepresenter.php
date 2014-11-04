<?php
namespace watoki\qrator\representer\basic;

use watoki\qrator\Representer;
use watoki\qrator\representer\property\ObjectProperty;
use watoki\qrator\representer\property\PropertyReader;
use watoki\qrator\representer\Property;

abstract class BasicRepresenter implements Representer {

    /**
     * @return string
     */
    public function getName() {
        $class = new \ReflectionClass($this->getClass());
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $class->getShortName());
    }

    /**
     * @param object $object
     * @return mixed
     */
    public function getId($object) {
        $properties = $this->getProperties($object);
        if (isset($properties['id']) && $properties['id']->canGet()) {
            return $properties['id']->get($object);
        } else {
            return null;
        }
    }

    /**
     * @param object $object
     * @return string
     */
    public function toString($object) {
        $propertyString = '';
        $properties = $this->getProperties($object);
        if (!$properties->isEmpty()) {
            $propertyString =
                ' [' .
                $properties
                    ->filter(function (Property $property) use ($object) {
                        return $property->canGet() && $property->get($object);
                    })
                    ->map(function (Property $property) use ($object) {
                        return $property->name() . ':' . print_r($property->get($object), true);
                    })
                    ->asList()
                    ->join('|')
                . ']';
        }
        return $this->getName() . $propertyString;
    }

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\qrator\representer\Property[] indexed by property name
     */
    public function getProperties($object = null) {
        $reader = new PropertyReader($this->getClass());
        return $reader->readProperties($object);
    }

} 