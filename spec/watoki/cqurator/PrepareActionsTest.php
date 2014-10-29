<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\QueryResource;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
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
            public function setThree() {}
        ');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass();
        }, 'ComplexAction');
    }

    function testAllPropertiesGiven() {
        $this->givenTheRequestParameter_Is('one', 'uno');
        $this->givenTheRequestParameter_Is('two', 'dos');
        $this->givenTheRequestParameter_Is('three', 'tres');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->thenTheResultShouldBeDisplayed();
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
        $this->request = new WebRequest(Url::fromString('http://cqurator.com'), new Path());
    }

    private function givenTheRequestParameter_Is($key, $value) {
        $this->request->getArguments()->set($key, $value);
    }

    private function whenIExecuteTheAction($action) {
        $resource = new QueryResource($this->dispatcher->dispatcher, $this->registry->registry);
        $this->returned = $resource->doGet($action);
    }

    private function thenTheResultShouldBeDisplayed() {
        $this->assertNotNull($this->returned['entity']);
    }

} 