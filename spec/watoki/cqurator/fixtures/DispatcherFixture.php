<?php
namespace spec\watoki\cqurator\fixtures;

use watoki\cqurator\ActionDispatcher;
use watoki\scrut\Fixture;

class DispatcherFixture extends Fixture {

    /** @var ActionDispatcher */
    public $dispatcher;

    /** @var TestObject[] */
    public $objects = [];

    public function setUp() {
        parent::setUp();
        $this->dispatcher = new ActionDispatcher($this->spec->factory);
    }

    public function givenAnObject($object) {
        $this->objects[$object] = new TestObject();
    }

    public function givenIAdded_AsHandlerFor($object, $action) {
        $this->dispatcher->addActionHandler($action, $this->objects[$object]);
    }

    public function givenIAddedTheClass_AsHandlerFor($class, $action) {
        $this->dispatcher->addActionHandler($action, $class);
    }

    public function givenIAddedTheClosure_AsHandlerFor($callable, $action) {
        $this->dispatcher->addActionHandler($action, $callable);
    }

    public function thenTheMethod_Of_ShouldBeInvoked($method, $object) {
        $this->spec->assertContains($method, $this->objects[$object]->called);
    }

    public function thenTheMethodOf_ShouldBeInvokedWithAnInstanceOf($object, $class) {
        $this->spec->assertInstanceOf($class, $this->objects[$object]->args[0][0]);
    }
}

class TestObject {

    public $called = array();

    public $args = array();

    function __call($name, $arguments) {
        $this->called[] = $name;
        $this->args[] = $arguments;
        return new \StdClass();
    }


}