<?php
namespace spec\watoki\qrator;

use watoki\collections\Liste;
use watoki\curir\delivery\WebResponse;
use watoki\curir\responder\MultiResponder;
use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * Actions are executed and the object or array ob objects presented together with their actions.
 *
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class ShowActionResultTest extends Specification {

    public function background() {
        $this->class->givenTheClass('MyAction');
    }

    function testNoActions() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass;
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenTheTitleShouldbe('My Action');
        $this->thenTheNameShouldBe('std Class');
        $this->thenThereShouldBeNoActions();

        $this->thenThe_ShouldNotBeShown('table');
    }

    function testDisplayActions() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \DateTime();
        }, 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');

        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionTwo', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherTwo', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherThree', 'DateTime');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Actions(5);

        $this->thenAction_ShouldHaveTheCaption(1, 'Action One');
        $this->thenAction_ShouldLinkTo(1, 'execute?action=ActionOne');
        $this->thenAction_ShouldHaveTheCaption(3, 'Another One');
        $this->thenAction_ShouldLinkTo(3, 'execute?action=AnotherOne');
    }

    function testActionsWithConfirmation() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \DateTime();
        }, 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionTwo', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionThree', 'DateTime');

        $this->givenISet_ToRequireConfirmation('ActionOne');
        $this->givenISet_ToRequireConfirmationWith('ActionTwo', 'Really?');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Actions(3);
        $this->thenAction_ShouldRequireConfirming(1, 'Really Action One?');
        $this->thenAction_ShouldRequireConfirming(2, 'Really?');
        $this->thenAction_ShouldNotRequireConfirmation(3);
    }

    function testDisplayCollection() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return [new \DateTime(), new \DateTime(), new \DateTime()];
        }, 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('ActionOne', 'DateTime');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('AnotherOne', 'DateTime');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Entities(3);
        $this->thenEntity_ShouldHave_Properties(1, 3);
        $this->thenEntity_ShouldHave_Actions(1, 2);

        $this->thenThe_ShouldNotBeShown('list');
    }

    function testDisplayCollectionObject() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new Liste([new \StdClass, new \StdClass]);
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenThereShouldBe_Entities(2);
    }

    function testEntityActionsWithArgument() {
        $this->class->givenTheClass_WithTheBody('property\Entity', '
            public $id = "42";
        ');
        $this->class->givenTheClass_WithTheBody('property\MyHandler', '
            function myAction() {
                return new Entity();
            }
        ');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('property\MyHandler', 'MyAction');

        $this->registry->givenIRegisteredAnEntityRepresenterFor('property\Entity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAction', 'property\Entity');
        $this->registry->givenIAddedTheAction_ToTheRepresenterOf('SomeAnother', 'property\Entity');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenAction_ShouldLinkTo(1, 'execute?action=SomeAction&args[id]=42');
        $this->thenAction_ShouldLinkTo(2, 'execute?action=SomeAnother&args[id]=42');
    }

    function testNoResult() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return null;
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->thenAnAlertShouldSay("Empty result.");
        $this->resource->then_ShouldBe('isPreparing', false);
    }

    function testResultIsAResponse() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new WebResponse('Hello World');
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->resource->thenTheResponseBodyShouldBe('Hello World');
    }

    function testResultIsAResponder() {
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new MultiResponder('Hey there');
        }, 'MyAction');

        $this->whenIShowTheResultsOf('MyAction');
        $this->resource->thenItShouldReturn(new MultiResponder('Hey there'));
    }

    ###########################################################################################

    private function whenIShowTheResultsOf($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, ExecuteResource::class);
    }

    private function thenTheNameShouldBe($string) {
        $this->resource->then_ShouldBe('entity/name', $string);
    }

    private function thenThereShouldBe_Actions($int) {
        $this->resource->thenThereShouldBe_Of($int, 'entity/actions/item');
    }

    private function thenThereShouldBeNoActions() {
        $this->resource->then_ShouldBe('entity/actions/isEmpty', true);
    }

    private function thenAction_ShouldHaveTheCaption($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/actions/item/$int/caption", $string);
    }

    private function thenAction_ShouldLinkTo($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("entity/actions/item/$int/link/href", $string);
    }

    private function thenThereShouldBe_Entities($int) {
        $this->resource->thenThereShouldBe_Of($int, "entity");
    }

    private function thenEntity_ShouldHave_Properties($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/$pos/properties/item");
    }

    private function thenEntity_ShouldHave_Actions($pos, $count) {
        $pos--;
        $this->resource->thenThereShouldBe_Of($count, "entity/$pos/actions/item");
    }

    private function thenAnAlertShouldSay($string) {
        $this->resource->then_ShouldBe('alert', $string);
    }

    private function thenThe_ShouldNotBeShown($element) {
        $this->resource->then_ShouldExist("$element/class");
    }

    private function thenAction_ShouldRequireConfirming($int, $message) {
        $int--;
        $this->resource->then_ShouldBe("entity/actions/item/$int/link/onclick", "return confirm('$message');");
    }

    private function thenAction_ShouldNotRequireConfirmation($int) {
        $int--;
        $this->resource->then_ShouldBe("entity/actions/item/$int/link/onclick", "return true;");
    }

    private function givenISet_ToRequireConfirmation($class) {
        $this->registry->givenIRegisteredAnActionRepresenterFor($class);
        $this->registry->representers[$class]->setRequireConfirmation();
    }

    private function givenISet_ToRequireConfirmationWith($class, $message) {
        $this->registry->givenIRegisteredAnActionRepresenterFor($class);
        $this->registry->representers[$class]->setRequireConfirmation($message);
    }

    private function thenTheTitleShouldbe($string) {
        $this->assertEquals($string, $this->resource->get('title'));
    }

} 