<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\QueryResource;
use watoki\scrut\Specification;

/**
 * Actions (Commands & Queries) can have properties which have to be filled before executing the Action.
 *
 * This is done by assigning request parameters to the properties of the Action. Missing properties are requested
 * from the user. Properties are determined with public instance variables and setter methods.
 *
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 * @property \spec\watoki\cqurator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\cqurator\fixtures\ResourceFixture resource <-
 */
class PrepareActionsTest extends Specification {

    public function background() {
        $this->class->givenTheClass_WithTheBody('ComplexAction', '
            public $one;
            public $two;

            private $that;
            public function setThree($v) { $this->that = $v; }
            public function getThat() { return $this->that; }
        ');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass();
        }, 'ComplexAction');
    }

    function testAllPropertiesGiven() {
        $this->resource->givenTheRequestArgument_Is('one', 'uno');
        $this->resource->givenTheRequestArgument_Is('two', 'dos');
        $this->resource->givenTheRequestArgument_Is('three', 'tres');

        $this->class->givenTheClass_WithTheBody('allGiven\MyHandler', '
            public static $action;
            public function complexAction($action) {
                self::$action = $action;
                return new \StdClass();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('allGiven\MyHandler', 'ComplexAction');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->resource->thenIShouldNotBeRedirected();

        $this->class->then_ShouldBe('allGiven\MyHandler::$action->one', 'uno');
        $this->class->then_ShouldBe('allGiven\MyHandler::$action->two', 'dos');
        $this->class->then_ShouldBe('allGiven\MyHandler::$action->getThat()', 'tres');
    }

    function testMissingProperty() {
        $this->resource->givenTheRequestArgument_Is('one', 'uno');
        $this->resource->givenTheRequestArgument_Is('three', 'tres');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->resource->thenIShouldBeRedirectedTo('prepare?action=ComplexAction&type=query&one=uno&three=tres');
    }

    function testGetActionInstanceFromFactory() {
        $this->class->givenTheClass('OtherClass');
        $this->givenISetAnInstanceOf_AsSingletonFor('OtherClass', 'ComplexAction');
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'OtherClass');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->resource->thenIShouldNotBeRedirected();
        $this->dispatcher->thenTheMethodOf_ShouldBeInvokedWithAnInstanceOf('myHandler', 'OtherClass');
    }

    ####################################################################################

    private function givenISetAnInstanceOf_AsSingletonFor($class, $action) {
        $this->factory->setSingleton($action, new $class);
    }

    private function whenIExecuteTheAction($action) {
        $this->resource->whenIDo_With(function (QueryResource $resource) use ($action) {
            return $resource->doGet($this->resource->request, $action);
        }, new QueryResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry));
    }

} 