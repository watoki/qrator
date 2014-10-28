<?php
namespace watoki\cqurator;

use watoki\factory\Factory;
use watoki\smokey\Dispatcher;
use watoki\smokey\EventDispatcher;

class ActionDispatcher implements Dispatcher {

    /** @var Dispatcher */
    private $dispatcher;

    /** @var \watoki\factory\Factory */
    private $factory;

    public function __construct(Factory $factory) {
        $this->factory = $factory;
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * @param string $class
     * @param object $handler
     */
    public function addActionHandler($class, $handler) {
        if (!is_callable($handler)) {
            $classReflection = new \ReflectionClass($class);
            $methodName = lcfirst($classReflection->getShortName());

            $handler = function ($action) use ($handler, $methodName) {
                $handler = is_object($handler) ? $handler : $this->factory->getInstance($handler);
                call_user_func(array($handler, $methodName), $action);
            };
        }
        $this->dispatcher->addListener($class, $handler);
    }

    /**
     * @param mixed $event
     * @return \watoki\smokey\Result
     */
    public function fire($event) {
        return $this->dispatcher->fire($event);
    }
}