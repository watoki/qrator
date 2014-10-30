<?php
namespace watoki\cqurator\web;

use watoki\collections\Map;
use watoki\cqurator\ActionDispatcher;
use watoki\cqurator\RepresenterRegistry;
use watoki\curir\Container;
use watoki\curir\protocol\Url;
use watoki\curir\rendering\adapter\TempanRenderer;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\factory\Factory;
use watoki\smokey\Dispatcher;

abstract class ActionResource extends Container {

    /** @var \watoki\factory\Factory */
    protected $factory;

    /** @var RepresenterRegistry */
    protected $registry;

    /** @var Dispatcher */
    protected $dispatcher;

    /**
     * @param Factory $factory <-
     * @param RepresenterRegistry $registry <-
     * @param ActionDispatcher $dispatcher <-
     */
    function __construct(Factory $factory, RepresenterRegistry $registry, ActionDispatcher $dispatcher) {
        parent::__construct($factory);
        $this->registry = $registry;
        $this->factory = $factory;
        $this->dispatcher = $dispatcher;
    }

    protected function createDefaultRenderer() {
        return new TempanRenderer();
    }

    protected function doAction(Dispatcher $dispatcher, Request $request, $actionClass, $type) {
        $result = null;

        $action = $this->createAction($actionClass);
        try {
            $this->prepareAction($request, $action);
        } catch (\UnderflowException $e) {
            return $this->redirectToPrepare($request, $actionClass, $type);
        }

        $dispatcher->fire($action)
            ->onSuccess(function ($returned) use (&$result) {
                $result = $returned;
            })
            ->onException(function (\Exception $e) {
                throw $e;
            });
        return $result;
    }

    protected function createAction($action) {
        return $this->factory->getInstance($action);
    }

    protected function redirectToPrepare(Request $request, $action, $type) {
        return $this->redirectTo('prepare', $request, array(
            'action' => $action,
            'type' => $type
        ));
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

    protected function redirectTo($resource, Request $request, $params = array()) {
        $target = Url::fromString($resource);
        $target->getParameters()->merge(new Map($params));
        $target->getParameters()->merge($request->getArguments());
        return new Redirecter($target);
    }

} 