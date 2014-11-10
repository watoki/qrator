<?php
namespace watoki\qrator;

use watoki\factory\Factory;
use watoki\qrator\representer\ActionLink;
use watoki\qrator\representer\generic\GenericEntityRepresenter;
use watoki\qrator\representer\MethodActionRepresenter;

class Registrar {

    /** @var RepresenterRegistry */
    private $registry;

    /** @var GenericEntityRepresenter */
    private $representer;

    /** @var Factory */
    private $factory;

    /** @var array Predicate and argument callables, indexed by action class */
    private $actions = [];

    function __construct($entityClass, RepresenterRegistry $registry, Factory $factory) {
        $this->registry = $registry;
        $this->factory = $factory;
        $this->representer = new GenericEntityRepresenter($entityClass);
        $this->registry->register($this->representer);

        $this->representer->setActions(function ($entity) {
            $actions = [];
            foreach ($this->actions as $action => $callables) {
                list($predicate, $arguments) = $callables;
                if (call_user_func($predicate, $entity)) {
                    $actions[] = new ActionLink($action, call_user_func($arguments, $entity));
                }
            }
            return $actions;
        });
    }

    /**
     * @return GenericEntityRepresenter
     */
    public function getRepresenter() {
        return $this->representer;
    }

    /**
     * @param string $class
     * @param string $method
     * @param null|callable $predicate
     * @param null|callable $arguments
     * @return MethodActionRepresenter
     */
    public function addMethodAction($class, $method, $predicate = null, $arguments = null) {
        $representer = new MethodActionRepresenter($class, $method, $this->factory);
        $this->registry->register($representer);

        $predicate = $predicate ?: function () {
            return true;
        };
        $arguments = $arguments ?: function ($entity) {
            if (isset($entity->id)) {
                return ['id' => $entity->id];
            } else if (method_exists($entity, 'getId')) {
                return ['id' => call_user_func([$entity, 'getId'])];
            } else {
                return [];
            }
        };

        $this->actions[$representer->getClass()] = [$predicate, $arguments];
        return $representer;
    }
}