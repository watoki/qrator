<?php
namespace watoki\cqurator;

use watoki\smokey\Dispatcher;
use watoki\smokey\EventDispatcher;

class ActionDispatcher implements Dispatcher {

    /** @var Dispatcher */
    private $dispatcher;

    public function __construct() {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * @param string $class
     * @param object $handler
     */
    public function addActionHandler($class, $handler) {
        $classReflection = new \ReflectionClass($class);
        $methodName = lcfirst($classReflection->getShortName());

        $this->dispatcher->addListener($class, function ($action) use ($handler, $methodName) {
            call_user_func(array($handler, $methodName), $action);
        });
    }

    /**
     * @param mixed $event
     * @return \watoki\smokey\Result
     */
    public function fire($event) {
        $this->dispatcher->fire($event);
    }
}