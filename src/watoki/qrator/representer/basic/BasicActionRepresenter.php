<?php
namespace watoki\qrator\representer\basic;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\qrator\ActionRepresenter;
use watoki\qrator\form\fields\ArrayField;
use watoki\qrator\form\fields\CheckboxField;
use watoki\qrator\form\fields\DateTimeField;
use watoki\qrator\form\fields\HiddenField;
use watoki\qrator\form\fields\SelectEntityField;
use watoki\qrator\form\fields\StringField;
use watoki\qrator\representer\Property;
use watoki\qrator\representer\property\types\ArrayType;
use watoki\qrator\representer\property\types\BooleanType;
use watoki\qrator\representer\property\types\ClassType;
use watoki\qrator\representer\property\types\IdentifierType;
use watoki\qrator\representer\property\types\MultiType;
use watoki\qrator\RepresenterRegistry;

abstract class BasicActionRepresenter extends BasicRepresenter implements ActionRepresenter {

    /** @var \watoki\factory\Factory */
    protected $factory;

    /** @var RepresenterRegistry */
    private $registry;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        $this->factory = $factory;
        $this->registry = $factory->getInstance(RepresenterRegistry::class);
    }

    /**
     * @return string
     */
    public function render() {
        return $this->getName();
    }

    /**
     * @param Map $args
     * @internal param $action
     * @return object
     */
    public function create(Map $args = null) {
        $args = $args ?: new Map();

        $inflated = [];
        foreach ($this->getProperties() as $property) {
            if ($args->has($property->name())) {
                $value = $args->get($property->name());
                $inflated[$property->name()] = $this->getField($property)->inflate($value);
            }
        }

        $action = $this->factory->getInstance($this->getClass(), $inflated);

        foreach ($this->getProperties($action) as $property) {
            if ($property->canSet() && $args->has($property->name())) {
                $property->set($action, $inflated[$property->name()]);
            }
        }

        return $action;
    }

    /**
     * @param object $object
     * @return array|\watoki\qrator\form\Field[]
     */
    public function getFields($object = null) {
        $fields = [];
        foreach ($this->getProperties($object) as $property) {
            if (!$property->canSet()) {
                continue;
            }

            $field = $this->getField($property);
            $fields[$property->name()] = $field;

            if ($object && $property->canGet() && $property->get($object) !== null) {
                $field->setValue($property->get($object));
            } else if ($property->defaultValue()) {
                $field->setValue($property->defaultValue());
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
    protected function getField(Property $property) {
        if ($property->name() == 'id') {
            return new HiddenField('id');
        }
        return $this->getFieldForType($property->name(), $property->type());
    }

    /**
     * @param $name
     * @param $type
     * @return \watoki\qrator\form\Field
     * @throws \Exception
     */
    protected function getFieldForType($name, $type) {
        if ($type instanceof BooleanType) {
            return new CheckboxField($name);
        } else if ($type instanceof ArrayType) {
            return new ArrayField($name, $this->getFieldForType($name, $type->getItemType()));
        } else if ($type instanceof IdentifierType) {
            return new SelectEntityField($name, $type->getTarget(), $this->registry);
        } else if ($type instanceof MultiType) {
            return $this->getFieldForType($name, $type->getTypes()[0]);
        } else if ($type instanceof ClassType) {
            return $this->getFieldForClass($name, $type->getClass());
        } else {
            return new StringField($name);
        }
    }

    /**
     * @param $name
     * @param $class
     * @throws \Exception
     * @return \watoki\qrator\form\Field
     */
    protected function getFieldForClass($name, $class) {
        switch ($class) {
            case \DateTime::class:
                return new DateTimeField($name);
            default:
                throw new \Exception("Class [$class] cannot be mapped to a field.");
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
     * @param object $result
     * @return null|\watoki\qrator\representer\ActionLink
     */
    public function getFollowUpAction($result) {
        return null;
    }

    /**
     * @param array|\watoki\qrator\form\Field[] $fields
     * @return void
     */
    public function preFill($fields) {
    }

    /**
     * @return string|null
     */
    public function requiresConfirmation() {
        return false;
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