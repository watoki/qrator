<?php
namespace watoki\cqurator\web;

use watoki\cqurator\RepresenterRegistry;
use watoki\curir\Container;
use watoki\curir\protocol\Url;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\factory\Factory;

abstract class ActionResource extends Container {

    /** @var \watoki\factory\Factory */
    protected $factory;

    /** @var RepresenterRegistry */
    protected $registry;

    /**
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     */
    function __construct(Factory $factory, RepresenterRegistry $registry) {
        parent::__construct($factory);
        $this->registry = $registry;
        $this->factory = $factory;
    }


    protected function createAction($action) {
        return $this->factory->getInstance($action);
    }

    protected function redirectToPrepare(Request $request, $action, $type) {
        return $this->redirectTo('prepare', $request, $action, $type);
    }

    protected function prepareAction(Request $request, $action) {
        $actionClass = get_class($action);
        $representer = $this->registry->getRepresenter($actionClass);

        foreach ($representer->getProperties($action) as $property) {
            if ($property->canSet()) {

                if (!$request->getArguments()->has($property->name)) {
                    throw new \UnderflowException("Property [{$property->name}] for action [$actionClass] missing");
                }
                $value = $request->getArguments()->get($property->name);
                $inflated = $representer->getField($property->name)->inflate($value);
                $property->set($inflated);
            }
        }
    }

    protected function redirectTo($resource, Request $request, $action, $type) {
        $target = Url::fromString($resource);
        $target->getParameters()->set('action', $action);
        $target->getParameters()->set('type', $type);
        $target->getParameters()->merge($request->getArguments());
        return new Redirecter($target);
    }

} 