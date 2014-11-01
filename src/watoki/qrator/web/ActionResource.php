<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\protocol\Url;
use watoki\curir\rendering\adapter\TempanRenderer;
use watoki\curir\responder\Redirecter;
use watoki\factory\exception\InjectionException;
use watoki\factory\Factory;
use watoki\qrator\ActionDispatcher;
use watoki\qrator\RepresenterRegistry;
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

    /**
     * @param $action
     * @param Map $args
     * @param $prepared
     * @return \watoki\curir\responder\Redirecter
     */
    protected function doAction($action, Map $args, $prepared) {
        $representer = $this->registry->getActionRepresenter($action);

        try {
            $object = $representer->create($action, $args);

            if (!$prepared && $representer->hasMissingProperties($object)) {
                return $this->redirectToPrepare($action, $args);
            }

            return $this->fireAction($object);
        } catch (InjectionException $e) {
            return $this->redirectToPrepare($action, $args);
        }
    }

    protected function fireAction($action) {
        $result = null;
        $this->dispatcher->fire($action)
            ->onSuccess(function ($returned) use (&$result) {
                $result = $returned;
            })
            ->onException(function (\Exception $e) {
                throw $e;
            });
        return $result;
    }

    protected function redirectToPrepare($action, Map $args) {
        return $this->redirectTo('prepare', $args, array(
            'action' => $action
        ));
    }

    protected function redirectTo($target, Map $args, $params = array()) {
        $target = Url::fromString($target);
        $target->getParameters()->merge(new Map($params));
        $target->getParameters()->set('args', $args);
        return new Redirecter($target);
    }

} 