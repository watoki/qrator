<?php
namespace spec\watoki\qrator\fixtures;

use watoki\scrut\Fixture;

/**
 * @property RegistryFixture registry <-
 */
class DispatcherFixture extends Fixture {

    /** @var TestObject[] */
    public $objects = [];

    public function givenAnObject($object) {
        $this->objects[$object] = new TestObject();
    }

    private function assureRepresenter($class) {
        $this->registry->givenIRegisteredAnActionRepresenterFor($class);
    }

    public function givenISet_AsHandlerFor($object, $action) {
        $this->assureRepresenter($action);
        if (!array_key_exists($object, $this->objects)) {
            $this->givenAnObject($object);
        }
        $this->registry->representers[$action]->setHandler($this->objects[$object]);
    }

    public function givenIAddedTheClass_AsHandlerFor($class, $action) {
        $this->assureRepresenter($action);
        $this->registry->representers[$action]->setHandler($class);
    }

    public function givenIAddedTheClosure_AsHandlerFor($callable, $action) {
        $this->assureRepresenter($action);
        $this->registry->representers[$action]->setHandler($callable);
    }

    public function thenTheMethod_Of_ShouldBeInvoked($method, $object) {
        $this->spec->assertContains($method, $this->objects[$object]->called);
    }

    public function thenTheMethodOf_ShouldBeInvokedWithAnInstanceOf($object, $class) {
        $this->spec->assertInstanceOf($class, $this->objects[$object]->args[0][0]);
    }

    public function givenISetAnEmptyHandlerFor($action) {
        $this->givenIAddedTheClosure_AsHandlerFor(function () {
        }, $action);
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