<?php
namespace spec\watoki\qrator\form\fields;

use watoki\qrator\form\fields\ArrayField;
use watoki\qrator\form\fields\StringField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class ArrayFieldTest extends Specification {

    function testWrapNameOfInnerField() {
        $this->givenAndArrayField_OfStringFields('tests', 'test');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldContain('args[tests][]');
    }

    function testStripLabelsAndIds() {
        $this->givenAndArrayField_OfStringFields('tests', 'test');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldNotContain('<label for="test"');
        $this->field->thenTheOutputShouldNotContain('id="test"');
    }

    function testRender() {
        $this->givenAndArrayField_OfStringFields('tests', 'test');
        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
            <label for="tests">Tests</label>
            <div id="tests"></div>
            <button class="btn btn-default" type="button" onclick="document.getElementById(\'tests\').appendChild(document.getElementById(\'tests-inner\').lastChild.cloneNode(true)); return false;">Add</button>
            <div id="tests-inner" style="display: none"><div>
                <input  class="form-control" type="text" name="args[tests][]"/>
            </div></div>');
    }

    /**
     * @param $outerName
     * @param $innerName
     */
    private function givenAndArrayField_OfStringFields($outerName, $innerName) {
        $this->field->givenTheField(new ArrayField($outerName, new StringField($innerName)));
    }

} 