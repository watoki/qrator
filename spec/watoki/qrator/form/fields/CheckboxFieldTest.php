<?php
namespace spec\watoki\qrator\form\fields;

use watoki\qrator\form\fields\CheckboxField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class CheckboxFieldTest extends Specification {

    protected function background() {
        $this->field->givenTheField(new CheckboxField('test'));
    }

    function testUnchecked() {
        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
        <div>
            <label>
                <input name="args[test]" type="checkbox" value="on">
                Test</label>
        </div>');
    }

    function testChecked() {
        $this->field->givenTheValueIs(true);
        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
        <div>
            <label>
                <input name="args[test]" type="checkbox" checked value="on">
                Test</label>
        </div>');
    }

    function testInflate() {
        $this->field->whenIInflate('anything');
        $this->field->thenItShouldReturn(true);
    }

} 