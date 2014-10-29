<?php
namespace watoki\cqurator\web;

use watoki\curir\Container;
use watoki\curir\protocol\Url;
use watoki\curir\responder\Redirecter;
use watoki\deli\Request;
use watoki\factory\Factory;

abstract class ActionResource extends Container {

    /** @var \watoki\factory\Factory */
    protected $factory;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
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

        $getParameter = function ($property) use ($request, $actionClass) {
            if (!$request->getArguments()->has($property)) {
                throw new \UnderflowException("Property [$property] for action [$actionClass] missing");
            }
            return $request->getArguments()->get($property);
        };

        foreach ($action as $property => $value) {
            $action->$property = $getParameter($property);
        }
        foreach (get_class_methods($actionClass) as $method) {
            if (substr($method, 0, 3) == 'set') {
                call_user_func(array($action, $method), $getParameter(lcfirst(substr($method, 3))));
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