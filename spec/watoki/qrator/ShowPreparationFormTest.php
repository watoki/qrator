<?php
namespace spec\watoki\qrator;

use watoki\qrator\form\fields\HiddenField;
use watoki\qrator\web\ExecuteResource;
use watoki\scrut\Specification;

/**
 * The only reason a form is ever presented is to fill the missing properties of an Action during preparation.
 *
 * @property \spec\watoki\reflect\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 * @property \spec\watoki\qrator\fixtures\DispatcherFixture dispatcher <-
 */
class ShowPreparationFormTest extends Specification {

    public function background() {
        $this->class->givenTheClass_WithTheBody('PrepareAction', '
            public $one;
            public $two;
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('PrepareAction');
    }

    function testInputForMissingProperties() {
        $this->resource->givenTheActionArgument_Is('one', 'uno');

        $this->whenIExecute('PrepareAction');

        $this->thenTheTitleShouldBe('Prepare Action');
        $this->thenThereShouldBeAHiddenField_WithValue('action', 'PrepareAction');

        $this->thenThereShouldBe_Fields(2);
        $this->thenField_ShouldHaveTheLabel(1, 'One');
        $this->thenField_ShouldBeHaveTheName(1, 'args[one]');
        $this->thenField_ShouldTheValue(1, 'uno');
        $this->thenField_ShouldBeHaveTheName(2, 'args[two]');
        $this->thenField_ShouldHaveNoValue(2);
    }

    function testUnCamelCaseLabels() {
        $this->class->givenTheClass_WithTheBody('camelCase\Action', '
            public $someProperty;
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('camelCase\Action');
        $this->whenIExecute('camelCase\Action');

        $this->thenThereShouldBe_Fields(1);
        $this->thenField_ShouldHaveTheLabel(1, 'Some Property');
    }

    function testGetFormDefinitionFromRepresenter() {
        $this->class->givenTheClass_Extending_WithTheBody('MySpecialField', '\watoki\qrator\form\Field', '
            public function render() { return "Hello World"; }
        ');
        $this->registry->givenIRegisteredAnActionRepresenterFor('PrepareAction');
        $this->givenISetTheFieldFor_To_For('one', 'MySpecialField', 'PrepareAction');

        $this->whenIExecute('PrepareAction');
        $this->thenField_ShouldBeRenderedAs(1, 'Hello World');
    }

    function testPreFillForm() {
        $this->class->givenTheClass_WithTheBody('PreFillingAction', '
            public $one;
            public $two;
            public $three;
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('PreFillingAction');
        $this->resource->givenTheActionArgument_Is('two', 'SeventyThree');

        $this->registry->givenIRegisteredAnActionRepresenterFor('PreFillingAction');
        $this->registry->givenIHaveSetFor_ThePrefiller('PreFillingAction', function ($fields) {
            /** @var \watoki\qrator\form\Field[] $fields */
            $fields['one']->setValue("FortyTwo");
        });

        $this->whenIExecute('PreFillingAction');
        $this->thenField_ShouldTheValue(1, 'FortyTwo');
        $this->thenField_ShouldTheValue(2, 'SeventyThree');
        $this->thenField_ShouldHaveNoValue(3);
    }

    function testPreFillFormWithoutActionInstance() {
        $this->class->givenTheClass_WithTheBody('PreFillingActionWithoutInstance', '
            public $two;
            function __construct($one) {}
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('PreFillingActionWithoutInstance');
        $this->resource->givenTheActionArgument_Is('one', 'FortyTwo');

        $this->whenIExecute('PreFillingActionWithoutInstance');
        $this->thenField_ShouldTheValue(1, 'FortyTwo');
    }

    function testHideIdFieldByDefault() {
        $this->resource->givenTheActionArgument_Is('id', '42');
        $this->class->givenTheClass_WithTheBody('ActionWithId', '
            public $id;
            public $other;
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('ActionWithId');

        $this->whenIExecute('ActionWithId');

        $this->thenThereShouldBe_Fields(2);
        $this->thenField_ShouldBeAHiddenField(1);
        $this->thenField_ShouldBeHaveTheName(1, 'args[id]');
        $this->thenField_ShouldTheValue(1, '42');
    }

    function testHideLabelOfHiddenFields() {
        $this->class->givenTheClass_WithTheBody('ActionWithHiddenField', '
            public $foo;
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('ActionWithHiddenField');
        $this->registry->givenISetTheField_Of_To('foo', 'ActionWithHiddenField', new HiddenField('foo'));

        $this->whenIExecute('ActionWithHiddenField');

        $this->thenThereShouldBe_Fields(1);
        $this->thenField_ShouldBeInvisible(1);
    }

    function testMakeFieldsRequired() {
        $this->class->givenTheClass_WithTheBody('preparation\ActionWithConstructor', '
            public $one;
            public $two;
            public $three;
            function __construct($one, $two = null) {}
        ');
        $this->dispatcher->givenISetAnEmptyHandlerFor('preparation\ActionWithConstructor');

        $this->resource->givenTheActionArgument_Is('one', 'uno');

        $this->whenIExecute('preparation\ActionWithConstructor');
        $this->thenThereShouldBe_Fields(3);

        $this->thenField_ShouldBeRequired(1);
        $this->thenField_ShouldNotBeRequired(2);
        $this->thenField_ShouldNotBeRequired(3);
    }

    function testActionWithMissingConstructorArguments() {
        $this->class->givenTheClass_WithTheBody('preparation\IncompleteConstructor', '
            public $three;
            function __construct($one, $two) {}
            function setFour($f) {}
        ');
        $this->whenIExecute('preparation\IncompleteConstructor');
        $this->thenThereShouldBe_Fields(4);
    }

    function testFillFormWithDefaultValues() {
        $this->class->givenTheClass_WithTheBody('defaultValue\SomeAction', '
            public $three = "tres";
            public $two;
            function __construct($one, $two = "dos") {}
        ');
        $this->whenIExecute('defaultValue\SomeAction');
        $this->thenThereShouldBe_Fields(3);

        $this->thenField_ShouldHaveNoValue(1);
        $this->thenField_ShouldTheValue(2, 'dos');
        $this->thenField_ShouldTheValue(3, 'tres');
    }

    ###############################################################################################

    private function whenIExecute($action) {
        $this->resource->whenIDo_With(function (ExecuteResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, ExecuteResource::class);
    }

    private function thenThereShouldBe_Fields($int) {
        $this->resource->thenThereShouldBe_Of($int, 'form/field');
    }

    private function thenField_ShouldHaveTheLabel($int, $string) {
        $int--;
        $this->resource->then_ShouldContain("form/field/$int", $string);
    }

    private function thenField_ShouldBeRenderedAs($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("form/field/$int", $string);
    }

    private function thenTheTitleShouldBe($string) {
        $this->resource->then_ShouldBe('title', $string);
    }

    private function thenThereShouldBeAHiddenField_WithValue($name, $value) {
        $parameters = $this->resource->get('form/parameter');
        foreach ($parameters as $hidden) {
            if ($hidden['name'] == $name && $hidden['value'] == $value) {
                return;
            }
        }
        $this->fail("Could not find parameter [$name] with value [$value] in " . print_r($parameters, true));
    }

    private function givenISetTheFieldFor_To_For($field, $class, $representedClass) {
        $this->registry->representers[$representedClass]->setField($field, new $class($field));
    }

    private function thenField_ShouldBeAHiddenField($int) {
        $this->assertContains('type="hidden"', $this->getRenderedField($int));
    }

    private function thenField_ShouldBeHaveTheName($int, $string) {
        $this->assertContains('name="' . $string . '"', $this->getRenderedField($int));
    }

    private function thenField_ShouldTheValue($int, $string) {
        $this->assertContains('value="' . $string . '"', $this->getRenderedField($int));
    }

    private function thenField_ShouldHaveNoValue($int) {
        $this->assertNotContains('value=', $this->getRenderedField($int));
    }

    private function thenField_ShouldBeRequired($int) {
        $this->assertContains('required', $this->getRenderedField($int));
    }

    private function thenField_ShouldNotBeRequired($int) {
        $this->assertNotContains('required', $this->getRenderedField($int));
    }

    private function getRenderedField($int) {
        $int--;
        return $this->resource->get("form/field/$int");
    }

    private function thenField_ShouldBeInvisible($pos) {
        $pos--;
        $this->resource->then_ShouldContain("form/field/$pos", 'type="hidden"');
    }

}