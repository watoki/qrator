<?php
namespace watoki\qrator\representer;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\qrator\ActionRepresenter;
use watoki\qrator\form\Field;
use watoki\qrator\form\fields\StringField;

class GenericActionRepresenter extends GenericRepresenter implements ActionRepresenter {

    /** @var array|Field[] */
    private $fields = [];

    /** @var Factory */
    private $factory;

    /** @var null|ActionGenerator */
    private $followUpAction;

    /** @var callable */
    private $handler;

    /** @var callable */
    private $preFiller;

    /**
     * @param string $class
     * @param Factory $factory <-
     */
    public function __construct($class, Factory $factory) {
        parent::__construct($class);
        $this->factory = $factory;
        $this->handler = function () use ($class) {
            throw new \LogicException("No handler set for [$class]");
        };
        $this->preFiller = function ($action) {
        };
    }

    /**
     * @param callable|object|string $handler
     */
    public function setHandler($handler) {
        if (is_callable($handler)) {
            $this->handler = $handler;
        } else {
            $classReflection = new \ReflectionClass($this->getClass());
            $methodName = lcfirst($classReflection->getShortName());

            $this->handler = function ($action) use ($handler, $methodName) {
                $handler = is_object($handler) ? $handler : $this->factory->getInstance($handler);
                if (!method_exists($handler, $methodName) && !method_exists($handler, '__call')) {
                    $class = get_class($handler);
                    throw new \InvalidArgumentException("Method [$class::$methodName] does not exist.");
                }
                return call_user_func(array($handler, $methodName), $action);
            };
        }
    }

    /**
     * @param object $object of the action to be executed
     * @return mixed
     */
    public function execute($object) {
        return call_user_func($this->handler, $object);
    }

    /**
     * @param object|string $object
     * @return array|\watoki\qrator\form\Field[]
     */
    public function getFields($object) {
        $fields = [];
        foreach ($this->getProperties($object) as $property) {
            if (!$property->canSet() || $property->name() == 'id') {
                continue;
            }

            $field = $this->getField($property->name());
            $fields[] = $field;

            if ($property->canGet()) {
                $field->setValue($property->get());
            }

            if ($property->isRequired()) {
                $field->setRequired(true);
            }
        }
        return $fields;
    }

    /**
     * @param $name
     * @return Field
     */
    public function getField($name) {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }
        return new StringField($name);
    }

    /**
     * @param string $name
     * @param Field $field
     */
    public function setField($name, Field $field) {
        $this->fields[$name] = $field;
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
                $inflated = $this->getField($property->name())->inflate($value);
                $property->set($inflated);
            }
        }

        return $action;
    }

    public function hasMissingProperties($object) {
        foreach ($this->getProperties($object) as $property) {
            if ($property->canGet() && $property->get() === null) {
                return true;
            }
        }
        return false;
    }

    public function setFollowUpAction(ActionGenerator $action) {
        $this->followUpAction = $action;
    }

    /**
     * @return null|\watoki\qrator\representer\ActionGenerator
     */
    public function getFollowUpAction() {
        return $this->followUpAction;
    }

    public function preFill($object) {
        call_user_func($this->preFiller, $object);
    }

    /**
     * @param callable $preFiller
     */
    public function setPreFiller($preFiller) {
        $this->preFiller = $preFiller;
    }
}