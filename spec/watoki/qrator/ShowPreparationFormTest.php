<?php
namespace spec\watoki\qrator;

use watoki\qrator\ActionDispatcher;
use watoki\qrator\web\PrepareResource;
use watoki\scrut\Specification;

/**
 * The only reason a form is ever presented is to fill the missing properties of an Action during preparation.
 *
 * @property \spec\watoki\qrator\fixtures\ClassFixture class <-
 * @property \spec\watoki\qrator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\qrator\fixtures\RegistryFixture registry <-
 */
class ShowPreparationFormTest extends Specification {

    public function background() {
        $this->class->givenTheClass_WithTheBody('PrepareAction', '
            public $one;
            public $two;
        ');
    }

    function testAllPropertiesProvided() {
        $this->resource->givenTheActionArgument_Is('one', 'uno');
        $this->resource->givenTheActionArgument_Is('two', 'dos');

        $this->whenIPrepare('PrepareAction');
        $this->resource->thenIShouldBeRedirectedTo('execute?action=PrepareAction&args[one]=uno&args[two]=dos');
    }

    function testInputForMissingProperties() {
        $this->resource->givenTheActionArgument_Is('one', 'uno');

        $this->whenIPrepare('PrepareAction');

        $this->thenTheFormTitleShouldBe('Prepare Action');
        $this->thenThereShouldBeAHiddenField_WithValue('action', 'PrepareAction');

        $this->thenThereShouldBe_Fields(2);
        $this->thenField_ShouldHaveTheLabel(1, 'One');
        $this->thenField_ShouldBeHaveTheName(1, 'args[one]');
        $this->thenField_ShouldBeHaveTheValue(1, 'uno');
        $this->thenField_ShouldBeHaveTheName(2, 'args[two]');
        $this->thenField_ShouldBeHaveNoValue(2);
    }

    function testGetFormDefinitionFromRepresenter() {
        $this->class->givenTheClass_Implementing_WithTheBody('MySpecialField', '\watoki\qrator\form\Field', '
            public function getLabel() { return "Some Label"; }
            public function render() { return "Hello World"; }
            public function setValue($value) {}
            public function inflate($value) { return $value; }
            public function setRequired($to = true) {}
            public function isRequired() {}
            public function getName() {}
        ');
        $this->registry->givenIRegisteredAnActionRepresenterFor('PrepareAction');
        $this->givenISetTheFieldFor_To_For('one', 'MySpecialField', 'PrepareAction');

        $this->whenIPrepare('PrepareAction');
        $this->thenField_ShouldHaveTheLabel(1, 'Some Label');
        $this->thenField_ShouldBeRenderedAs(1, 'Hello World');
    }

    function testPreFillForm() {
        $this->class->givenTheClass_Implementing_WithTheBody('PreFillingAction', '\watoki\qrator\form\PreFilling', '
            public $one;
            public $two;

            public function preFill(\watoki\qrator\ActionRepresenter $dispatcher) {
                $this->one = "Fourtytwo";
            }
        ');

        $this->whenIPrepare('PreFillingAction');
        $this->thenField_ShouldBeHaveTheValue(1, 'Fourtytwo');
        $this->thenField_ShouldBeHaveNoValue(2);
    }

    function testHideIdField() {
        $this->resource->givenTheActionArgument_Is('id', '42');
        $this->class->givenTheClass_WithTheBody('ActionWithId', '
            public $id;
            public $other;
        ');

        $this->whenIPrepare('ActionWithId');

        $this->thenThereShouldBe_Fields(1);
        $this->thenThereShouldBeAHiddenField_WithValue('args[id]', '42');
    }

    function testMakeFieldsRequired() {
        $this->class->givenTheClass_WithTheBody('preparation\ActionWithConstructor', '
            public $one;
            public $two;
            public $three;
            function __construct($one, $two = null) {}
        ');

        $this->resource->givenTheActionArgument_Is('one', 'uno');

        $this->whenIPrepare('preparation\ActionWithConstructor');
        $this->thenThereShouldBe_Fields(3);

        $this->thenField_ShouldBeRequired(1);
        $this->thenField_ShouldNotBeRequired(2);
        $this->thenField_ShouldNotBeRequired(3);
    }

    function testActionWithMissingConstructorArguments() {
        $this->class->givenTheClass_WithTheBody('preparation\IncompleteConstructor', '
            public $three;
            function __construct($one, $two) {}
            function setFour() {}
        ');
        $this->whenIPrepare('preparation\IncompleteConstructor');
        $this->thenThereShouldBe_Fields(4);
    }

    ###############################################################################################

    private function whenIPrepare($action) {
        $this->resource->whenIDo_With(function (PrepareResource $resource) use ($action) {
            return $resource->doGet($action, $this->resource->args);
        }, new PrepareResource($this->factory, $this->registry->registry));
    }

    private function thenThereShouldBe_Fields($int) {
        $this->resource->thenThereShouldBe_Of($int, 'form/field');
    }

    private function thenField_ShouldHaveTheLabel($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("form/field/$int/label", $string);
    }

    private function thenField_ShouldBeRenderedAs($int, $string) {
        $int--;
        $this->resource->then_ShouldBe("form/field/$int/control", $string);
    }

    private function thenTheFormTitleShouldBe($string) {
        $this->resource->then_ShouldBe('form/title', $string);
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
        $this->registry->representers[$representedClass]->setField($field, new $class);
    }

    private function thenField_ShouldBeHaveTheName($int, $string) {
        $this->assertContains('name="' . $string . '"', $this->getRenderedField($int));
    }

    private function thenField_ShouldBeHaveTheValue($int, $string) {
        $this->assertContains('value="' . $string . '"', $this->getRenderedField($int));
    }

    private function thenField_ShouldBeHaveNoValue($int) {
        $this->assertNotContains('value=', $this->getRenderedField($int));
    }

    private function thenField_ShouldBeRequired($int) {
        $this->assertContains('required', $this->getRenderedField($int));
        $int--;
        $this->resource->then_ShouldBe("form/field/$int/isRequired", true);
    }

    private function thenField_ShouldNotBeRequired($int) {
        $this->assertNotContains('required', $this->getRenderedField($int));
        $int--;
        $this->resource->then_ShouldBe("form/field/$int/isRequired", false);
    }

    private function getRenderedField($int) {
        $int--;
        return $this->resource->get("form/field/$int/control");
    }

}