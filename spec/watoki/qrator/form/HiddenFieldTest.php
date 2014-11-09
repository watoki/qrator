<?php
namespace spec\watoki\qrator\form;

use watoki\qrator\form\fields\HiddenField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class HiddenFieldTest extends Specification {

    function testRender() {
        $this->field->givenTheField(new HiddenField('test'));
        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('<input type="hidden" name="args[test]" value=""/>');
    }

} 