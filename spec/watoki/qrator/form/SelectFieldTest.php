<?php
namespace spec\watoki\qrator\form;

use watoki\qrator\form\fields\SelectField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class SelectFieldTest extends Specification {
    function testShowOptions() {
        $this->givenTheSelectField_WithTheOptions('test', [
            'one' => 'Uno',
            'two' => 'Dos'
        ]);

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
            <select class="form-control" name="args[test]">
                    <option value="one">Uno</option>
                    <option value="two">Dos</option>
            </select>');
    }

    function testSelectValue() {
        $this->givenTheSelectField_WithTheOptions('test', [
            'one' => 'Uno',
            'two' => 'Dos'
        ]);
        $this->field->givenTheValueIs('two');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
            <select class="form-control" name="args[test]">
                    <option value="one">Uno</option>
                    <option selected value="two">Dos</option>
            </select>');
    }

    ##########################################################################################

    private function givenTheSelectField_WithTheOptions($name, $options) {
        $this->field->givenTheField(new SelectField($name, $options));
    }

} 