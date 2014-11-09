<?php
namespace watoki\qrator\representer;

use watoki\factory\ClassResolver;
use watoki\qrator\representer\property\types\ArrayType;
use watoki\qrator\representer\property\types\BooleanType;
use watoki\qrator\representer\property\types\ClassType;
use watoki\qrator\representer\property\types\FloatType;
use watoki\qrator\representer\property\types\IdentifierType;
use watoki\qrator\representer\property\types\IntegerType;
use watoki\qrator\representer\property\types\MultiType;
use watoki\qrator\representer\property\types\NullableType;
use watoki\qrator\representer\property\types\StringType;

abstract class Property {

    const ID_SUFFIX = '-ID';

    private $name;

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

        if (substr($type, -strlen(self::ID_SUFFIX)) == self::ID_SUFFIX) {
            $type = substr($type, 0, -strlen(self::ID_SUFFIX));
            $resolved = $resolver->resolve($type);
            if ($resolved) {
                return new IdentifierType($resolved);
            }
            return null;
        }

        $resolved = $resolver->resolve($type);
        if ($resolved) {
            return new ClassType($resolved);
        }
        return null;
    }

} 