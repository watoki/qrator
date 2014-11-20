<?php
namespace spec\watoki\qrator;

use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * Actions can have properties which have to be filled before executing the Action.
 *
 * This is done by assigning request parameters to the properties of the Action. Missing properties are requested
 * from the user. Properties are determined with public instance variables and setter methods.
 *
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 */
class PrepareActionsTest extends Specification {

    public function background() {
        $this->class->givenTheClass_WithTheBody('ComplexAction', '
            function __construct($one, $two = "dos") {
                $this->one = $one;
                $this->two = $two;
            }
            public $three = "tres";
            public $four;
        ');
        $this->dispatcher->givenIAddedTheClosure_AsHandlerFor(function () {
            return new \StdClass();
        }, 'ComplexAction');
    }

    function testNoResultIfActionCannotBeCreated() {
        $this->whenIExecuteTheAction('ComplexAction');
        $this->thenItShouldShowNoResult();

        $this->thenTheField_ShouldHaveNoValue('one');
        $this->thenTheField_ShouldHaveTheValue('two', 'dos');
        $this->thenTheField_ShouldHaveTheValue('three', 'tres');
        $this->thenTheField_ShouldHaveNoValue('four');
    }

    function testShowResultIfActionCanBeCreated() {
        $this->resource->givenTheActionArgument_Is('one', 'uno');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->thenTheResultShouldBeShown();
        $this->thenTheField_ShouldHaveTheValue('one', 'uno');
    }

    function testKeepArgumentsInForm() {
        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('two', 'dos');

        $this->whenIExecuteTheAction('ComplexAction');
        $this->thenTheField_ShouldHaveTheValue('two', 'dos');
    }

    function testHideActionFormIfItHasNoFields() {
        $this->class->givenTheClass('EmptyAction');
        $this->dispatcher->givenISetAnEmptyHandlerFor('EmptyAction');

        $this->whenIExecuteTheAction('EmptyAction');
        $this->thenTheActionFormShouldNotBeThere();
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
        $this->class->givenTheClass_Extending_WithTheBody('inflateArgs\MySpecialField', '\watoki\qrator\form\Field', '
            public function render() {}
            public function inflate($value) { return new \DateTime($value); }
        ');

        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('inflateArgs\MyHandler', 'inflateArgs\InflatableAction');
        $this->resource->givenTheActionArgument_Is('inflateMe', '2012-03-04 15:16');

        $this->registry->givenIRegisteredAnActionRepresenterFor('inflateArgs\InflatableAction');
        $this->registry->givenISetTheField_Of_ToBeAnInstanceOf('inflateMe', 'inflateArgs\InflatableAction',
            'inflateArgs\MySpecialField');

        $this->whenIExecuteTheAction('inflateArgs\InflatableAction');
        $this->class->then_ShouldBe('inflateArgs\MyHandler::$action->inflateMe instanceof \DateTime', true);
        $this->class->then_ShouldBe('inflateArgs\MyHandler::$action->inflateMe->getTimestamp()', 1330874160);
    }

    function testInflateIdentifierTypes() {
        $this->class->givenTheClass('InflateIdentifierTypes\SomeEntity');
        $this->class->givenTheClass_WithTheBody('InflateIdentifierTypes\SomeEntityId', '
            function __construct($id) { $this->id = $id; }
            function __toString() { return $this->id; }'
        );
        $this->class->givenTheClass_WithTheBody('InflateIdentifierTypes\SomeAction', '
            /** @var string|SomeEntity-ID */
            public $string;
            /** @var SomeEntityId */
            public $object;
        ');

        $this->class->givenTheClass_WithTheBody('InflateIdentifierTypes\SomeHandler', '
            public static $action;
            public function someAction($action) {
                self::$action = $action;
                return new \StdClass();
            }
            public function listAction() {
                return [];
            }
        ');

        $this->class->givenTheClass('InflateIdentifierTypes\ListAction');
        $this->registry->givenIRegisteredAnEntityRepresenterFor('InflateIdentifierTypes\SomeEntity');
        $this->registry->givenIHaveSet_AsTheListActionFor('InflateIdentifierTypes\ListAction', 'InflateIdentifierTypes\SomeEntity');
        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('InflateIdentifierTypes\SomeHandler', 'InflateIdentifierTypes\ListAction');

        $this->dispatcher->givenIAddedTheClass_AsHandlerFor('InflateIdentifierTypes\SomeHandler', 'InflateIdentifierTypes\SomeAction');
        $this->resource->givenTheActionArgument_Is('object', 'some ID');
        $this->resource->givenTheActionArgument_Is('string', 'other ID');

        $this->registry->givenIRegisteredAnActionRepresenterFor('InflateIdentifierTypes\SomeAction');

        $this->whenIExecuteTheAction('InflateIdentifierTypes\SomeAction');

        $this->class->then_ShouldBe('InflateIdentifierTypes\SomeHandler::$action->string', 'other ID');

        $this->class->then_ShouldBe('InflateIdentifierTypes\SomeHandler::$action->object instanceof \InflateIdentifierTypes\SomeEntityId', true);
        $this->class->then_ShouldBe('InflateIdentifierTypes\SomeHandler::$action->object->__toString()', 'some ID');
    }

    ####################################################################################

    private function whenIExecuteTheAction($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, ExecuteResource::class);
    }

    private function thenItShouldShowNoResult() {
        $this->resource->then_ShouldBe('alert', null);
        $this->resource->then_ShouldBe('entity', null);
    }

    private function thenTheResultShouldBeShown() {
    }

    private function thenTheField_ShouldHaveNoValue($field) {
        $this->assertNotContains('value="', $this->findField($field));
    }

    private function thenTheField_ShouldHaveTheValue($field, $value) {
        $this->assertContains('value="' . $value . '"', $this->findField($field));
    }

    private function findField($name) {
        foreach ($this->resource->get('form/field') as $field) {
            if (strpos($field, 'name="args[' . $name . ']"')) {
                return $field;
            }
        }
        throw new \Exception("Field not found: $name in " . print_r($this->resource->get('form/field'), true));
    }

    private function thenTheActionFormShouldNotBeThere() {
        $this->resource->then_ShouldBe('form', null);
    }

} 