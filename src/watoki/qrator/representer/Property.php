<?php
namespace watoki\qrator\representer;

use watoki\factory\ClassResolver;
use watoki\qrator\representer\property\types\ArrayType;
use watoki\qrator\representer\property\types\BooleanType;
use watoki\qrator\representer\property\types\ClassType;
use watoki\qrator\representer\property\types\FloatType;
use watoki\qrator\representer\property\types\IdentifierObjectType;
use watoki\qrator\representer\property\types\IdentifierType;
use watoki\qrator\representer\property\types\IntegerType;
use watoki\qrator\representer\property\types\MultiType;
use watoki\qrator\representer\property\types\NullableType;
use watoki\qrator\representer\property\types\StringType;

abstract class Property {

    private $name;

    const TARGET_CONSTANT = 'TARGET';

    public function __construct($name) {
        $this->name = $name;
    }

    public function name() {
        return $this->name;
    }

    public function isRequired() {
        return false;
    }

    abstract public function type();

    abstract public function canGet();

    abstract public function canSet();

    abstract public function get($object);

    abstract public function set($object, $value);

    public function defaultValue() {
        return null;
    }

    protected function findType($pattern, $docComment, \ReflectionClass $class) {
        $matches = array();
        $found = preg_match($pattern, $docComment, $matches);
        if (!$found) {
            return null;
        }
        $type = $matches[1];

        if (strpos($type, '|') !== false) {
            $types = explode('|', $type);
        } else {
            $types = [$type];
        }

        return $this->getType($types, $class);
    }

    private function getType($types, \ReflectionClass $class) {
        if (count($types) > 1) {
            if (in_array('null', $types)) {
                $types = array_values(array_diff($types, array('null')));
                return new NullableType($this->getType($types, $class));
            } else if (in_array('array', $types)) {
                $types = array_values(array_diff($types, array('array')));
                $types = array_map(function ($type) {
                    return str_replace('[]', '', $type);
                }, $types);
                return new ArrayType($this->getType($types, $class));
            }
            return new MultiType(array_map(function ($type) use ($class) {
                return $this->getType([$type], $class);
            }, $types));
        }

        $type = $types[0];

        switch ($type) {
            case 'int':
            case 'integer':
            case 'long':
                return new IntegerType();
            case 'float':
            case 'double':
                return new FloatType();
            case 'string':
                return new StringType();
            case 'bool':
            case 'boolean':
                return new BooleanType();
        }

        $resolver = new ClassResolver($class);

        if (strtolower(substr($type, -3)) == '-id') {
            $resolved = $resolver->resolve(substr($type, 0, -3));
            if ($resolved) {
                return new IdentifierType($resolved);
            } else {
                return null;
            }
        }

        return $this->resolveClassType($type, $class);
    }

    /**
     * @param string $type
     * @param \ReflectionClass $class
     * @return null|ClassType|IdentifierType
     */
    protected function resolveClassType($type, \ReflectionClass $class) {
        $resolver = new ClassResolver($class);

        if (strtolower(substr($type, -2)) == 'id') {
            $resolved = $resolver->resolve($type);
            if ($resolved) {
                $class = new \ReflectionClass($resolved);
                if ($class->hasConstant(self::TARGET_CONSTANT)) {
                    return new IdentifierObjectType($class->getConstant(self::TARGET_CONSTANT), $resolved);
                } else if (class_exists(substr($class->getName(), 0, -2))) {
                    return new IdentifierObjectType(substr($class->getName(), 0, -2), $resolved);
                }
            }
        }

        $resolved = $resolver->resolve($type);
        if ($resolved) {
            return new ClassType($resolved);
        }
        return null;
    }

} 