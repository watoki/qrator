<?php
namespace spec\watoki\cqurator;

use watoki\cqurator\web\PrepareResource;
use watoki\cqurator\web\QueryResource;
use watoki\scrut\Specification;

/**
 * The only reason a form is ever presented is to fill the missing properties of an Action during preparation.
 *
 * @property \spec\watoki\cqurator\fixtures\ClassFixture class <-
 * @property \spec\watoki\cqurator\fixtures\ResourceFixture resource <-
 * @property \spec\watoki\cqurator\fixtures\RegistryFixture registry <-
 */
class ShowPreparationFormTest extends Specification {

    public function background() {
        $this->class->givenTheClass_WithTheBody('PrepareAction', '
            public $one;
            public $two;
        ');
    }

    function testAllPropertiesProvided() {
        $this->resource->givenTheRequestArgument_Is('one', 'uno');
        $this->resource->givenTheRequestArgument_Is('two', 'dos');

        $this->whenIPrepare('PrepareAction');
        $this->resource->thenIShouldBeRedirectedTo('query?action=PrepareAction&one=uno&two=dos');
    }

    function testInputForMissingProperties() {
        $this->resource->givenTheRequestArgument_Is('one', 'uno');

        $this->whenIPrepare('PrepareAction');

        $this->thenTheFormTitleShouldBe('PrepareAction');
        $this->thenThereShouldBeAHiddenField_WithValue('action', 'PrepareAction');
        $this->thenThereShouldBeAHiddenField_WithValue('type', 'query');

        $this->thenThereShouldBe_Fields(2);
        $this->thenField_ShouldHaveTheLabel(1, 'One');
        $this->thenField_ShouldBeRenderedAs(1, '<input type="text" name="one" value="uno"/>');
        $this->thenField_ShouldBeRenderedAs(2, '<input type="text" name="two"/>');
    }

    function testGetFormDefinitionFromRepresenter() {
        $this->class->givenTheClass_Implementing_WithTheBody('MySpecialField', '\watoki\cqurator\form\Field', '
            public function getLabel() { return "Some Label"; }
            public function render() { return "Hello World"; }
            public function setValue($value) {}
            public function inflate($value) { return $value; }
        ');
        $this->registry->givenIRegisteredARepresenterFor('PrepareAction');
        $this->givenISetTheFieldFor_To_For('one', 'MySpecialField', 'PrepareAction');

        $this->whenIPrepare('PrepareAction');
        $this->thenField_ShouldHaveTheLabel(1, 'Some Label');
        $this->thenField_ShouldBeRenderedAs(1, 'Hello World');
    }

    ###############################################################################################

    private $action;
    private $type;

    private function whenIPrepare($action) {
        $this->resource->whenIDo_With(function (PrepareResource $resource) use ($action) {
            return $resource->doGet($this->resource->request, $action, 'query');
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

}