<?php
namespace spec\watoki\qrator;

use watoki\qrator\web\CommandResource;
use watoki\qrator\web\QueryResource;
use watoki\curir\cookie\CookieStore;
use watoki\curir\cookie\SerializerRepository;
use watoki\scrut\Specification;

/**
 * Actions (Commands & Queries) can have properties which have to be filled before executing the Action.
 *
 * This is done by assigning request parameters to the properties of the Action. Missing properties are requested
 * from the user. Properties are determined with public instance variables and setter methods.
 *
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
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
        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('two', 'dos');
        $this->resource->givenTheActionArgument_Is('three', 'tres');

        $this->class->givenTheClass_WithTheBody('allGiven\MyHandler', '
            public static $action;
            public function complexAction($action) {
                self::$action = $action;
                return new \StdClass();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('allGiven\MyHandler', 'ComplexAction');

        $this->whenIExecuteTheQuery('ComplexAction');
        $this->resource->thenIShouldNotBeRedirected();

        $this->class->then_ShouldBe('allGiven\MyHandler::$action->one', 'uno');
        $this->class->then_ShouldBe('allGiven\MyHandler::$action->two', 'dos');
        $this->class->then_ShouldBe('allGiven\MyHandler::$action->getThat()', 'tres');
    }

    function testMissingProperty() {
        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('three', 'tres');

        $this->whenIExecuteTheQuery('ComplexAction');
        $this->resource->thenIShouldBeRedirectedTo('prepare?action=ComplexAction&type=query&args[one]=uno&args[three]=tres');

        $this->whenIExecuteTheCommand('ComplexAction');
        $this->resource->thenIShouldBeRedirectedTo('prepare?action=ComplexAction&type=command&args[one]=uno&args[three]=tres');
    }

    function testGetActionInstanceFromFactory() {
        $this->class->givenTheClass('OtherClass');
        $this->givenISetAnInstanceOf_AsSingletonFor('OtherClass', 'ComplexAction');
        $this->dispatcher->givenAnObject('myHandler');
        $this->dispatcher->givenIAdded_AsHandlerFor('myHandler', 'OtherClass');

        $this->whenIExecuteTheQuery('ComplexAction');
        $this->resource->thenIShouldNotBeRedirected();
        $this->dispatcher->thenTheMethodOf_ShouldBeInvokedWithAnInstanceOf('myHandler', 'OtherClass');
    }

    function testInflateArguments() {
        $this->class->givenTheClass_WithTheBody('inflateArgs\InflatableAction', '
            public $inflateMe;
        ');

        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass();
        }, 'inflateArgs\InflatableAction');

        $this->class->givenTheClass_WithTheBody('inflateArgs\MyHandler', '
            public static $action;
            public function inflatableAction($action) {
                self::$action = $action;
                return new \StdClass();
            }
        ');
        $this->class->givenTheClass_Implementing_WithTheBody('inflateArgs\MySpecialField', '\watoki\qrator\form\Field', '
            public function getLabel() {}
            public function render() {}
            public function setValue($value) {}
            public function inflate($value) { return new \DateTime($value); }
            public function setRequired($to = true) {}
            public function isRequired() {}
            public function getName() {}
        ');

        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('inflateArgs\MyHandler', 'inflateArgs\InflatableAction');
        $this->resource->givenTheActionArgument_Is('inflateMe', '2012-03-04 15:16');

        $this->registry->givenIRegisteredAnActionRepresenterFor('inflateArgs\InflatableAction');
        $this->givenISetTheField_Of_ToBeAnInstanceOf('inflateMe', 'inflateArgs\InflatableAction', 'inflateArgs\MySpecialField');

        $this->whenIExecuteTheQuery('inflateArgs\InflatableAction');
        $this->class->then_ShouldBe('inflateArgs\MyHandler::$action->inflateMe instanceof \DateTime', true);
        $this->class->then_ShouldBe('inflateArgs\MyHandler::$action->inflateMe->getTimestamp()', 1330874160);
    }

    function testMissingPropertiesButPrepared() {
        $this->whenIExecuteThePreparedAction('ComplexAction');
        $this->resource->thenIShouldNotBeRedirected();

        $this->whenIExecuteThePreparedCommand('ComplexAction');
        $this->resource->thenIShouldNotBeRedirected();
    }

    ####################################################################################

    private $prepared = false;

    private function givenISetAnInstanceOf_AsSingletonFor($class, $action) {
        $this->factory->setSingleton($action, new $class);
    }

    private function whenIExecuteTheQuery($action) {
        $cookies = new CookieStore(new SerializerRepository(), array());

        $this->resource->whenIDo_With(function (QueryResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args, $this->prepared);
        }, new QueryResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry, $cookies));
    }

    private function whenIExecuteThePreparedCommand($action) {
        $this->prepared = true;
        $this->whenIExecuteTheCommand($action);
    }

    private function whenIExecuteTheCommand($action) {
        $cookies = new CookieStore(new SerializerRepository(), array());

        $this->resource->whenIDo_With(function (CommandResource $resource) use ($action) {
            return $resource->doPost($action, $this->resource->args, $this->prepared);
        }, new CommandResource($this->factory, $this->dispatcher->dispatcher, $this->registry->registry, $cookies));
    }

    private function givenISetTheField_Of_ToBeAnInstanceOf($field, $class, $fieldClass) {
        $this->registry->representers[$class]->setField($field, new $fieldClass);
    }

    private function whenIExecuteThePreparedAction($action) {
        $this->prepared = true;
        $this->whenIExecuteTheQuery($action);
    }

} 