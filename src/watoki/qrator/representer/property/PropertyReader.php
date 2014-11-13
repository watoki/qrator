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
     * @param object|null $object If provided, dynamic (run-time) properties are read as well
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

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (AccessorProperty::isAccessor($method)) {
                $add(new AccessorProperty($method));
            }
        }

        return $properties;
    }

} 