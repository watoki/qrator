<?php
namespace watoki\qrator\representer\basic;

use watoki\qrator\Representer;
use watoki\reflect\PropertyReader;
use watoki\reflect\Property;

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
     * @return string
     */
    public function toString($object) {
        if (method_exists($object, '__toString')) {
            return (string)$object;
        }

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

        if (strlen($propertyString) > 21) {
            $propertyString = substr($propertyString, 0, 9) . '...' . substr($propertyString, -9);
        }

        return $this->getName() . $propertyString;
    }

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|\watoki\reflect\Property[] indexed by property name
     */
    public function getProperties($object = null) {
        $reader = new PropertyReader($this->getClass());
        return $reader->readInterface($object);
    }

} 