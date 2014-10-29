<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\QueryResource;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Path;
use watoki\deli\Request;
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
 */
class PrepareActionsTest extends Specification {

    protected function background() {
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
        $this->givenTheRequestParameter_Is('one', 'uno');
        $this->givenTheRequestParameter_Is('two', 'dos');
        $this->givenTheRequestParameter_Is('three', 'tres');

        $this->class->givenTheClass_WithTheBody('allGiven\MyHandler', '
            public static $action;
            public function complexAction($action) {
                self::$action = $action;
                return new \StdClass();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('allGiven\MyHandler', 'ComplexAction');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->thenTheResultShouldBeDisplayed();

        $this->class->then_ShouldBe('allGiven\MyHandler::$action->one', 'uno');
        $this->class->then_ShouldBe('allGiven\MyHandler::$action->two', 'dos');
        $this->class->then_ShouldBe('allGiven\MyHandler::$action->getThat()', 'tres');
    }

    function testMissingProperty() {
        $this->markTestIncomplete();
    }

    function testGetActionInstanceFromFactory() {
        $this->markTestIncomplete();
    }

    ####################################################################################

    /** @var WebRequest */
    private $request;

    private $returned;

    protected function setUp() {
        parent::setUp();
        $this->request = new Request(new Path(), new Path());
    }

    private function givenTheRequestParameter_Is($key, $value) {
        $this->request->getArguments()->set($key, $value);
    }

    private function whenIExecuteTheAction($action) {
        $resource = new QueryResource($this->dispatcher->dispatcher, $this->registry->registry);
        $this->returned = $resource->doGet($this->request, $action);
    }

    private function thenTheResultShouldBeDisplayed() {
        $this->assertNotNull($this->returned['entity']);
    }

} 