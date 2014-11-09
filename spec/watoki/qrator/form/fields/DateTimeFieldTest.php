<?php
namespace spec\watoki\qrator\form\fields;

use watoki\qrator\form\fields\DateTimeField;
use watoki\scrut\Specification;

/**
 * @property \spec\watoki\qrator\fixtures\FieldFixture field <-
 */
class DateTimeFieldTest extends Specification {

    function testRender() {
        $this->field->givenTheField(new DateTimeField('test'));
        $this->field->givenTheValueIs('2001-01-01');

        $this->field->whenIRenderTheField();
        $this->field->thenTheOutputShouldBe('
            <label for="test">Test</label>
            <input id="test" class="form-control date-time-field" type="text" name="args[test]" value="2001-01-01"/>');

        $this->field->thenItShouldAdd_ToTheHead(['jquery', 'jquery.datetimepicker']);
        $this->field->thenItShouldAdd_ToTheFoot(["
            <script>
                $('.date-time-field').datetimepicker();
            </script>"]);
    }

    function testInflate() {
        $this->field->givenTheField(new DateTimeField('test'));
        $this->field->whenIInflate('2001-01-01');
        $this->field->thenItShouldReturn(new \DateTime('2001-01-01'));
    }

} 