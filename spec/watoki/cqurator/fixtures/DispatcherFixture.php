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
        $this->dispatcher = new ActionDispatcher();
    }

    public function givenAnObject($object) {
        $this->objects[$object] = new TestObject();
    }

    public function givenIAdded_AsHandlerFor($object, $action) {
        $this->dispatcher->addActionHandler($action, $this->objects[$object]);
    }

    public function thenTheMethod_Of_ShouldBeInvoked($method, $object) {
        $this->spec->assertContains($method, $this->objects[$object]->called);
    }
}

class TestObject {

    public $called = array();

    public $args = array();

    function __call($name, $arguments) {
        $this->called[] = $name;
        $this->args[] = $arguments;
    }


}