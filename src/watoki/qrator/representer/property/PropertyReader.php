<?php
namespace watoki\qrator\representer\property;

use watoki\collections\Map;
use watoki\qrator\representer\Property;

class PropertyReader {

    private $class;

    function __construct($class) {
        $this->class = $class;
    }

    /**
     * @param object|null $object
     * @return \watoki\collections\Map|Property[] indexed by property name
     */
    public function readProperties($object = null) {
        $properties = new Map();
        $reflection = new \ReflectionClass($object ? : $this->class);

        $add = function (Property $property) use ($properties) {
            if (!$properties->has($property->name())) {
                $properties[$property->name()] = $property;
            } else {
                $multi = $properties[$property->name()];
                if (!($multi instanceof MultiProperty)) {
                    $multi = new MultiProperty($property->name());
                    $multi->add($properties[$property->name()]);
                    $properties[$property->name()] = $multi;
                }
                $multi->add($property);
            }
        };

        if ($reflection->getConstructor()) {
            foreach ($reflection->getConstructor()->getParameters() as $parameter) {
                $add(new ConstructorProperty($reflection->getConstructor(), $parameter));
            }
        }

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $add(new PublicProperty($property));
        }

        if (is_object($object)) {
            foreach ($object as $name => $value) {
                $add(new DynamicProperty($name));
            }
        }

        $accessors = array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), function (\ReflectionMethod $method) {
            return substr($method->getName(), 0, 3) == 'set' || substr($method->getName(), 0, 3) == 'get' && empty($method->getParameters());
        });

        foreach ($accessors as $method) {
            $add(new AccessorProperty($method));
        }

        return $properties;
    }

} 