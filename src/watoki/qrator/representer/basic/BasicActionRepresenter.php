<?php
namespace watoki\qrator\representer\basic;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\qrator\ActionRepresenter;
use watoki\qrator\form\fields\ArrayField;
use watoki\qrator\form\fields\SelectEntityField;
use watoki\qrator\form\fields\StringField;
use watoki\qrator\representer\Property;
use watoki\qrator\representer\property\types\ArrayType;
use watoki\qrator\representer\property\types\IdentifierType;
use watoki\qrator\representer\property\types\MultiType;
use watoki\qrator\representer\property\types\StringType;
use watoki\qrator\RepresenterRegistry;

abstract class BasicActionRepresenter extends BasicRepresenter implements ActionRepresenter {

    /** @var \watoki\factory\Factory */
    protected $factory;

    /** @var RepresenterRegistry */
    private $registry;

    /**
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     */
    function __construct(Factory $factory, RepresenterRegistry $registry) {
        $this->factory = $factory;
        $this->registry = $registry;
    }

    /**
     * @param Map $args
     * @internal param $action
     * @return object
     */
    public function create(Map $args = null) {
        $args = $args ? : new Map();
        $action = $this->factory->getInstance($this->getClass(), $args->toArray());

        foreach ($this->getProperties($action) as $property) {
            if ($property->canSet() && $args->has($property->name())) {
                $value = $args->get($property->name());
                $inflated = $this->getField($property)->inflate($value);
                $property->set($action, $inflated);
            }
        }

        return $action;
    }

    /**
     * @param object $object
     * @return array|\watoki\qrator\form\Field[]
     */
    public function getFields($object) {
        $fields = [];
        foreach ($this->getProperties($object) as $property) {
            if (!$property->canSet() || $property->name() == 'id') {
                continue;
            }

            $field = $this->getField($property);
            $fields[] = $field;

            if (is_object($object) && $property->canGet()) {
                $field->setValue($property->get($object));
            }

            if ($property->isRequired()) {
                $field->setRequired(true);
            }
        }
        return $fields;
    }

    /**
     * @param \watoki\qrator\representer\Property $property
     * @return \watoki\qrator\form\Field
     */
    public function getField(Property $property) {
        return $this->getFieldForType($property->name(), $property->type());
    }

    protected function getFieldForType($name, $type) {
        if ($type instanceof ArrayType) {
            return new ArrayField($name, $this->getFieldForType($name, $type->getItemType()));
        } else if ($type instanceof IdentifierType) {
            return new SelectEntityField($name, $type->getTarget(), $this->registry);
        } else if ($type instanceof MultiType) {
            return $this->getFieldForType($name, $type->getTypes()[0]);
        } else {
            return new StringField($name);
        }
    }

    /**
     * @param object $object
     * @return bool
     */
    public function hasMissingProperties($object) {
        foreach ($this->getProperties($object) as $property) {
            if ($property->canGet() && $property->get($object) === null) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return null|\watoki\qrator\representer\ActionGenerator
     */
    public function getFollowUpAction() {
        return null;
    }

    /**
     * @param object $object
     * @return void
     */
    public function preFill($object) {
    }

    /**
     * @param callable|object|string $handler
     * @param object $object
     * @return mixed
     */
    protected function executeHandler($handler, $object) {
        return call_user_func($this->makeCallable($handler), $object);
    }

    /**
     * @param callable|object|string $handler
     * @return callable
     */
    protected function makeCallable($handler) {
        if (is_callable($handler)) {
            return $handler;
        } else {
            $classReflection = new \ReflectionClass($this->getClass());
            $methodName = lcfirst($classReflection->getShortName());

            return function ($action) use ($handler, $methodName) {
                $handler = is_object($handler) ? $handler : $this->factory->getInstance($handler);
                if (!method_exists($handler, $methodName) && !method_exists($handler, '__call')) {
                    $class = get_class($handler);
                    throw new \InvalidArgumentException("Method [$class::$methodName] does not exist.");
                }
                return call_user_func(array($handler, $methodName), $action);
            };
        }
    }
}