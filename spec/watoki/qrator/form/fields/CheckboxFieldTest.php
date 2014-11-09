<?php
namespace spec\watoki\qrator\form\fields;

use watoki\qrator\form\fields\CheckboxField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class CheckboxFieldTest extends Specification {

    function testRender() {
        $this->field->givenTheField(new CheckboxField('test'));
        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
        <div class="checkbox">
            <label>
                <input name="args[test]" type="checkbox" value="on">
                Test</label>
        </div>');
    }

} 