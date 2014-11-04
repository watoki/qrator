<?php
namespace watoki\qrator\web;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\protocol\Url;
use watoki\curir\rendering\adapter\TempanRenderer;
use watoki\curir\responder\Redirecter;
use watoki\factory\Factory;
use watoki\qrator\RepresenterRegistry;

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

    protected function createDefaultRenderer() {
        return new TempanRenderer();
    }

    protected function redirectTo($target, Map $args, $params = array()) {
        $target = Url::fromString($target);
        $target->getParameters()->merge(new Map($params));
        $target->getParameters()->set('args', $args);
        return new Redirecter($target);
    }

} 